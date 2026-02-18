<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocumentForRAG;
use App\Models\SubmittedDocument;
use App\Models\DocumentDownload;
use App\Models\LegalDocument;
use App\Models\Utility;
use App\Services\AdvancedRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentController extends Controller
{
    private AdvancedRagService $advancedRag;

    public function __construct(AdvancedRagService $advancedRag)
    {
        $this->advancedRag = $advancedRag;
    }

    /**
     * Upload and analyze user document
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        Log::debug('🔵 [UPLOAD] Starting document upload process');
        Log::debug('🔵 [UPLOAD] Request data:', $request->all());
        
        // 🔧 MAX_FILE_SIZE = 30MB (30 * 1024 = 30720 KB)
        $maxFileSizeKb = 30 * 1024;
        
        Log::debug('🔵 [UPLOAD] Validating file with max size: ' . $maxFileSizeKb . ' KB');
        
        $validator = Validator::make($request->all(), [
            'file' => "required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,webp|max:{$maxFileSizeKb}", // 30MB max + images with OCR
            'title' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ [UPLOAD] Validation failed:', $validator->errors()->toArray());
            
            // 🔧 User-friendly error messages for file size
            $errors = $validator->errors()->toArray();
            if (isset($errors['file'])) {
                foreach ($errors['file'] as $i => $msg) {
                    if (str_contains($msg, 'may not be greater than')) {
                        $fileSize = $request->file('file') ? round($request->file('file')->getSize() / 1024 / 1024, 2) : '?';
                        $errors['file'][$i] = "Fichier trop volumineux. Maximum : 30 MB. Fichier fourni : {$fileSize} MB";
                    }
                }
            }
            return response()->json([
                'success' => false,
                'errors' => $errors,
            ], 422);
        }

        Log::debug('✅ [UPLOAD] File validation passed');
        
        $user = $request->user();
        Log::debug('🔵 [UPLOAD] User ID: ' . ($user ? $user->id : 'NULL'));

        // Check quota - get active subscription
        Log::debug('🔵 [UPLOAD] Checking subscription quota');
        $subscription = $user->getActiveMobileAppSubscription();
        
        if (!$subscription || !$subscription->plan) {
            Log::error('❌ [UPLOAD] No active subscription', ['user_id' => $user->id]);
            return $this->quotaErrorResponse('ai_analysis', $subscription, 'Abonnement actif requis pour analyser des documents.', 'subscription_required');
        }

        if (!$subscription->canUseAIAnalysis()) {
            Log::warning('❌ [UPLOAD] AI analysis quota exceeded', ['user_id' => $user->id]);
            return $this->quotaErrorResponse('ai_analysis', $subscription, 'Quota d\'analyse IA dépassé. Veuillez mettre à niveau votre plan.');
        }

        Log::debug('✅ [UPLOAD] Subscription quota OK');

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            Log::debug('🔵 [UPLOAD] File info:', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'temp_path' => $file->getRealPath(),
            ]);
            
            // Configure storage disk - Use the configured disk (r2, s3, wasabi, or public)
            $settings = Utility::settings();
            $storageSetting = $settings['storage_setting'] ?? 'local';
            
            Log::debug('🔵 [UPLOAD] Storage setting: ' . $storageSetting);
            
            // Map storage_setting to Laravel disk name (same as Utility::get_file() uses)
            $diskMap = [
                'r2' => 'r2',
                's3' => 's3',
                'wasabi' => 'wasabi',
                'local' => 'public',  // 'local' storage_setting uses 'public' disk in Laravel
            ];
            $disk = $diskMap[$storageSetting] ?? 'public';
            
            Log::debug('🔵 [UPLOAD] Using disk: ' . $disk);
            
            // Configure the selected disk dynamically (R2, S3, Wasabi)
            if ($storageSetting === 'r2') {
                Log::debug('🔵 [UPLOAD] Configuring R2 disk');
                config([
                    'filesystems.disks.r2.key' => $settings['r2_key'],
                    'filesystems.disks.r2.secret' => $settings['r2_secret'],
                    'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                    'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
                    'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
                    'filesystems.disks.r2.url' => $settings['r2_url'],
                ]);
            } elseif ($storageSetting === 's3') {
                Log::debug('🔵 [UPLOAD] Configuring S3 disk');
                config([
                    'filesystems.disks.s3.key' => $settings['s3_key'],
                    'filesystems.disks.s3.secret' => $settings['s3_secret'],
                    'filesystems.disks.s3.region' => $settings['s3_region'],
                    'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                    'filesystems.disks.s3.endpoint' => $settings['s3_endpoint'] ?? null,
                ]);
            } elseif ($storageSetting === 'wasabi') {
                Log::debug('🔵 [UPLOAD] Configuring Wasabi disk');
                config([
                    'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                    'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                    'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                    'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                    'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com',
                ]);
            }

            // Upload file to the configured disk
            // Store in 'documents/' folder (same structure as legal_documents/, templates/, fiscal_resources/)
            Log::debug('🔵 [UPLOAD] Uploading file to storage...');
            $filePath = $file->storeAs('documents', $fileName, $disk);
            Log::debug('✅ [UPLOAD] File uploaded successfully', ['path' => $filePath]);

            // Create document record with pending status
            Log::debug('🔵 [UPLOAD] Creating database record');
            $document = SubmittedDocument::create([
                'user_id' => $user->id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $fileName,
                'storage_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'processing_status' => 'pending',
                'extracted_text' => null,
                'extracted_text_length' => 0,
            ]);
            Log::debug('✅ [UPLOAD] Document record created', ['document_id' => $document->id]);

            // Execute IMMEDIATE synchronous extraction + indexing (NOT queued)
            // This ensures users can query the document RIGHT AWAY after upload
            Log::debug('🔵 [UPLOAD] Starting IMMEDIATE document processing (sync)');
            try {
                ProcessDocumentForRAG::dispatchSync($document->id);
                Log::debug('✅ [UPLOAD] Document processed IMMEDIATELY');
            } catch (\Exception $e) {
                Log::error('❌ [UPLOAD] Immediate processing failed: ' . $e->getMessage());
                // Continue - document is uploaded, just not processed yet
            }

            Log::info('Document uploaded and processed immediately', [
                'document_id' => $document->id,
                'user_id' => $user->id,
            ]);

            // Increment AI usage
            Log::debug('🔵 [UPLOAD] Incrementing AI usage quota');
            $alerts = $subscription->incrementAIAnalysis();
            $subscription->refresh(); // Refresh to avoid stale data
            Log::debug('✅ [UPLOAD] AI usage incremented');
            
            // Generate file URL using Utility::get_file() (same as DocumentTemplate, LegalDocument)
            $fileUrl = Utility::get_file($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully. Processing in background.',
                'data' => [
                    'document' => [
                        'id' => $document->id,
                        'title' => $document->original_filename,
                        'file_name' => $document->original_filename,
                        'file_type' => $this->getFileTypeFromMime($document->mime_type),
                        'mime_type' => $document->mime_type,
                        'file_size' => $document->file_size,
                        'file_url' => $fileUrl,
                        'processing_status' => $document->processing_status,
                        'uploaded_at' => $document->created_at->format('Y-m-d H:i:s'),
                        'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                    ],
                    'quotas' => $this->quotaPayload($subscription, 'ai_analysis'),
                    'alerts' => $alerts,
                ],
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('❌ [UPLOAD] Database error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_type' => 'database_error',
            ], 500);
        } catch (\Exception $e) {
            Log::error('❌ [UPLOAD] Unexpected error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Document upload failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        }
    }

    /**
     * Get user's submitted documents
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserDocuments(Request $request)
    {
        $user = $request->user();

        $documents = SubmittedDocument::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                // Use getFileUrlAttribute() via Utility::get_file()
                $fileUrl = $doc->file_url;  // Automatically uses getFileUrlAttribute()
                
                return [
                    'id' => $doc->id,
                    'title' => $doc->original_filename,
                    'file_name' => $doc->original_filename,
                    'file_type' => $this->getFileTypeFromMime($doc->mime_type),
                    'mime_type' => $doc->mime_type,
                    'file_size' => $doc->file_size,
                    'file_url' => $fileUrl,
                    'processing_status' => $doc->processing_status,
                    'processing_error' => $doc->processing_error,
                    'has_sensitive_data' => $doc->has_sensitive_data,
                    'detections_count' => $doc->detections_count,
                    'uploaded_at' => $doc->created_at->format('Y-m-d H:i:s'),
                    'created_at' => $doc->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $documents,
        ], 200);
    }

    /**
     * Get legal library documents (searchable)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchLegalDocuments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:legal_categories,id',
            'jurisdiction' => 'nullable|string|max:10',
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->load('activeMobileSubscription.plan');
        $subscription = $user->activeMobileSubscription;

        if (!$subscription || !$subscription->plan) {
            return $this->quotaErrorResponse('search', $subscription, 'Abonnement actif requis pour rechercher dans la bibliothèque.', 'subscription_required');
        }

        if (!$subscription->canSearch()) {
            return $this->quotaErrorResponse('search', $subscription, 'Quota de recherche dépassé. Veuillez mettre à niveau votre plan.');
        }

        try {
            $query = $request->input('query', '');
            $jurisdiction = $request->input('jurisdiction', $user->jurisdiction ?? 'CM');
            $categoryId = $request->category_id;
            $limit = $request->input('limit', 20);
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $limit;

            $documentsQuery = LegalDocument::query()
                ->select([
                    'legal_documents.id',
                    'legal_documents.title',
                    'legal_documents.description',
                    'legal_documents.category_id',
                    'legal_documents.country',
                    'legal_documents.file_name',
                    'legal_documents.file_size',
                    'legal_documents.downloads_count',
                    'legal_documents.views_count',
                    'legal_documents.created_at',
                ])
                ->with('category:id,name');

            // Filtrage par pays (jurisdiction) - Si données existent pour ce pays
            if ($jurisdiction && $jurisdiction !== 'ALL') {
                $countWithCountry = LegalDocument::where('country', $jurisdiction)->count();
                if ($countWithCountry > 0) {
                    $documentsQuery->where('country', $jurisdiction);
                }
                // Sinon, retourner tous les documents (pas de filtre pays)
            }

            // Si une query est fournie, faire une recherche
            if (!empty($query) && strlen($query) >= 2) {
                $searchTerm = strtolower($query);
                $documentsQuery->where(function($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(file_name) LIKE ?', ["%{$searchTerm}%"]);
                });
            }

            if ($categoryId) {
                $documentsQuery->where('category_id', $categoryId);
            }

            // Total count for pagination
            $total = $documentsQuery->count();
            $totalPages = ceil($total / $limit);

            $documents = $documentsQuery
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title ?? 'Document sans titre',
                        'description' => $doc->description,
                        'category' => $doc->category->name ?? 'N/A',
                        'category_id' => $doc->category_id,
                        'file_name' => $doc->file_name,
                        'file_size' => $doc->formatted_file_size,
                        'downloads_count' => $doc->downloads_count ?? 0,
                        'views_count' => $doc->views_count ?? 0,
                        'created_at' => $doc->created_at->format('Y-m-d'),
                    ];
                });

            // Increment search usage only if a search was performed
            $alerts = null;
            if (!empty($query)) {
                $alerts = $subscription->incrementSearch();
            }

            return response()->json([
                'success' => true,
                'results' => $documents,
                'total' => $total,
                'page' => $page,
                'per_page' => $limit,
                'total_pages' => $totalPages,
                'quotas' => $this->quotaPayload($subscription, 'search'),
                'alerts' => $alerts,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Legal search error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get legal library categories
     */
    public function getLegalCategories(Request $request)
    {
        try {
            $categories = LegalCategory::withCount('documents')
                ->orderBy('name')
                ->get()
                ->map(function($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'documents_count' => $category->documents_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $categories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
            ], 500);
        }
    }

    /**
     * Download legal document
     * 
     * @param Request $request
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadLegalDocument(Request $request, $documentId)
    {
        $user = $request->user();
        
        Log::info('Legal document download request', [
            'user_id' => $user->id,
            'document_id' => $documentId,
        ]);
        
        $subscription = $user->mobileAppSubscription()->where('status', 'active')->first();

        if (!$subscription) {
            Log::warning('No active mobile subscription for user', ['user_id' => $user->id]);
            return $this->quotaErrorResponse('pdf_download', $subscription, 'Pas d\'abonnement actif. Veuillez vous abonner pour télécharger des documents.', 'subscription_required');
        }

        if (!$subscription->canDownloadPDF()) {
            Log::warning('PDF download quota exceeded', [
                'user_id' => $user->id,
                'used' => $subscription->pdf_downloads_used,
                'limit' => $subscription->plan->pdf_downloads_limit,
            ]);
            return $this->quotaErrorResponse('pdf_download', $subscription, 'Quota de téléchargement PDF dépassé. Veuillez mettre à niveau votre plan.');
        }

        $document = LegalDocument::find($documentId);

        if (!$document) {
            Log::warning('Legal document not found', ['document_id' => $documentId]);
            return response()->json([
                'success' => false,
                'message' => 'Document introuvable',
            ], 404);
        }

        Log::info('Found legal document', [
            'document_id' => $documentId,
            'file_path' => $document->file_path,
            'file_name' => $document->file_name,
        ]);

        // Vérifier que le file_path n'est pas vide
        if (empty($document->file_path)) {
            Log::error('File path is empty for document', [
                'document_id' => $documentId,
                'file_name' => $document->file_name,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Le chemin du fichier n\'est pas configuré. Veuillez réuploader le document.',
            ], 404);
        }

        // Get file URL
        $url = Utility::get_file($document->file_path);

        if (empty($url)) {
            Log::error('File not accessible', [
                'document_id' => $documentId,
                'file_path' => $document->file_path,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Fichier non accessible',
            ], 404);
        }

        Log::info('Generated download URL', [
            'document_id' => $documentId,
            'url_scheme' => parse_url($url, PHP_URL_SCHEME),
        ]);

        // Increment document downloads_count
        $document->incrementDownloads();

        // Record download
        DocumentDownload::create([
            'user_id' => $user->id,
            'legal_document_id' => $documentId,
        ]);

        // Increment download usage
        $alerts = $subscription->incrementPDFDownload();

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $url,
                'file_name' => $document->file_name,
                'quotas' => $this->quotaPayload($subscription, 'pdf_download'),
                'alerts' => $alerts,
            ],
        ], 200);
    }

    /**
     * Delete user document
     * 
     * @param Request $request
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDocument(Request $request, $documentId)
    {
        $user = $request->user();

        $document = SubmittedDocument::where('id', $documentId)
            ->where('user_id', $user->id)
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        // Delete from storage using the same disk logic as upload
        $settings = Utility::settings();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        
        // Map storage_setting to Laravel disk name
        $diskMap = [
            'r2' => 'r2',
            's3' => 's3',
            'wasabi' => 'wasabi',
            'local' => 'public',
        ];
        $disk = $diskMap[$storageSetting] ?? 'public';
        
        // Configure the selected disk
        if ($storageSetting === 'r2') {
            config([
                'filesystems.disks.r2.key' => $settings['r2_key'],
                'filesystems.disks.r2.secret' => $settings['r2_secret'],
                'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
                'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
                'filesystems.disks.r2.url' => $settings['r2_url'],
            ]);
        } elseif ($storageSetting === 's3') {
            config([
                'filesystems.disks.s3.key' => $settings['s3_key'],
                'filesystems.disks.s3.secret' => $settings['s3_secret'],
                'filesystems.disks.s3.region' => $settings['s3_region'],
                'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                'filesystems.disks.s3.endpoint' => $settings['s3_endpoint'] ?? null,
            ]);
        } elseif ($storageSetting === 'wasabi') {
            config([
                'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com',
            ]);
        }

        // Determine stored path (new field `storage_path`, fallback to legacy `file_path`)
        $path = $document->storage_path ?: $document->file_path;

        // Guard against null/empty path to avoid Flysystem errors
        if ($path && is_string($path) && !empty(trim($path))) {
            try {
                // Delete quietly if exists; ignore return value
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                    Log::info('Document file deleted from storage', [
                        'document_id' => $document->id,
                        'disk' => $disk,
                        'path' => $path,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Storage delete failed (non-blocking)', [
                    'document_id' => $document->id,
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                // Continue execution even if file deletion fails
            }
        } else {
            Log::warning('No valid storage path on document; skipping file delete', [
                'document_id' => $document->id,
                'storage_path' => $document->storage_path ?? 'NULL',
                'file_path' => $document->file_path ?? 'NULL',
            ]);
        }

        // Delete from Pinecone
        $this->advancedRag->deleteDocument($document->id);

        // Delete record
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully',
        ], 200);
    }

    /**
     * Extract text from PDF file
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string     * Anonymize document content
     * Détecte et remplace automatiquement les données sensibles
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function anonymize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $content = $request->input('content');
            $anonymizationService = app(\App\Services\AnonymizationService::class);
            
            $result = $anonymizationService->anonymizeDocument($content);
            
            Log::info('Document anonymisé via API', [
                'user_id' => $request->user()?->id,
                'has_sensitive_data' => $result['has_sensitive_data'],
                'detections_count' => count($result['detections']),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'anonymized_content' => $result['anonymized_content'],
                    'detections' => $result['detections'],
                    'summary' => $anonymizationService->summarizeDetections($result['detections']),
                    'has_sensitive_data' => $result['has_sensitive_data'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur anonymisation document', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'anonymisation du document',
            ], 500);
        }
    }

    /**
     * Private method to extract text from document (PDF, DOC, DOCX)
     */
    private function extractTextFromPdf($file): string
    {
        try {
            $mimeType = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();
            
            // Handle PDF files
            if ($mimeType === 'application/pdf' || $extension === 'pdf') {
                try {
                    if (class_exists('Smalot\PdfParser\Parser')) {
                        $parser = new PdfParser();
                        $pdf = $parser->parseFile($file->getRealPath());
                        $text = $pdf->getText();
                        
                        // Clean text
                        $text = preg_replace('/\s+/', ' ', $text ?? '');
                        $text = trim($text);
                        
                        return $text;
                    } else {
                        \Log::warning("PdfParser not available, skipping text extraction");
                        return '';
                    }
                } catch (\Exception $e) {
                    \Log::warning("PDF text extraction failed: " . $e->getMessage());
                    return '';
                }
            }
            // Handle DOC/DOCX files - return empty for now
            // TODO: Implement DOC/DOCX extraction using PhpWord or similar
            else {
                \Log::info("DOC/DOCX extraction not yet implemented, storing without text extraction");
                return '';
            }
        } catch (\Exception $e) {
            \Log::error("Document text extraction failed: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extract file type from mime type
     */
    private function getFileTypeFromMime(string $mimeType): string
    {
        if (strpos($mimeType, 'pdf') !== false) {
            return 'pdf';
        } elseif (strpos($mimeType, 'word') !== false || strpos($mimeType, 'document') !== false) {
            return 'docx';
        } elseif (strpos($mimeType, 'msword') !== false) {
            return 'doc';
        } elseif (strpos($mimeType, 'spreadsheet') !== false || strpos($mimeType, 'sheet') !== false) {
            return 'xlsx';
        } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'ms-excel') !== false) {
            return 'xls';
        } elseif (strpos($mimeType, 'presentation') !== false || strpos($mimeType, 'powerpoint') !== false) {
            return 'pptx';
        } elseif (strpos($mimeType, 'ms-powerpoint') !== false) {
            return 'ppt';
        } elseif (strpos($mimeType, 'text') !== false) {
            return 'txt';
        } elseif (strpos($mimeType, 'image') !== false) {
            return 'image';
        } else {
            return 'unknown';
        }
    }

    /**
     * Format file size in human-readable format
     */
    private function formatFileSize($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    private function quotaErrorResponse(string $feature, $subscription, string $message, string $code = 'quota_exceeded')
    {
        $payload = [
            'success' => false,
            'code' => $code,
            'feature' => $feature,
            'message' => $message,
        ];

        if ($subscription) {
            $payload['quotas'] = $this->quotaPayload($subscription, $feature);
        }

        return response()->json($payload, 403);
    }

    private function quotaPayload($subscription, string $feature): array
    {
        $map = [
            'ai_analysis' => 'ai_analyses',
            'search' => 'searches',
            'pdf_download' => 'pdf_downloads',
        ];

        $base = $map[$feature] ?? $feature;
        $usedField = $base . '_used';
        $limitField = $base . '_limit';

        return [
            'feature' => $feature,
            'used' => $subscription->$usedField ?? 0,
            'limit' => $subscription->plan ? ($subscription->plan->$limitField ?? 0) : 0,
            'remaining' => ($subscription->plan && isset($subscription->$usedField))
                ? max(0, ($subscription->plan->$limitField ?? 0) - ($subscription->$usedField))
                : 0,
            'reset_at' => $subscription->quota_reset_at?->toIso8601String(),
        ];
    }

    /**
     * Track when user views/opens a legal document
     * 
     * @param Request $request
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewLegalDocument(Request $request, $documentId)
    {
        $user = $request->user();
        
        Log::info('Legal document view request', [
            'user_id' => $user->id,
            'document_id' => $documentId,
        ]);

        $document = LegalDocument::find($documentId);

        if (!$document) {
            Log::warning('Legal document not found', ['document_id' => $documentId]);
            return response()->json([
                'success' => false,
                'message' => 'Document introuvable',
            ], 404);
        }

        // Increment document views_count
        $document->incrementViews();

        Log::info('Legal document view tracked', [
            'document_id' => $documentId,
            'new_views_count' => $document->views_count,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vue enregistrée',
            'data' => [
                'views_count' => $document->views_count,
                'downloads_count' => $document->downloads_count,
            ],
        ], 200);
    }
}
