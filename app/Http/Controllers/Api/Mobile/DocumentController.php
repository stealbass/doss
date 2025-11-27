<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\SubmittedDocument;
use App\Models\DocumentDownload;
use App\Models\LegalDocument;
use App\Models\Utility;
use App\Services\AdvancedRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
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
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf|max:51200', // 50MB
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check quota
        $subscription = $user->mobileAppSubscription()->where('status', 'active')->first();
        
        if (!$subscription || !$subscription->canUseAIAnalysis()) {
            return response()->json([
                'success' => false,
                'message' => 'AI analysis quota exceeded. Please upgrade your plan.',
            ], 403);
        }

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            
            // Configure storage disk
            $settings = Utility::settings();
            $storageSetting = $settings['storage_setting'] ?? 'local';
            
            if ($storageSetting === 'r2') {
                // Configure R2 dynamically
                config([
                    'filesystems.disks.r2.key' => $settings['r2_key'],
                    'filesystems.disks.r2.secret' => $settings['r2_secret'],
                    'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                    'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
                    'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
                    'filesystems.disks.r2.url' => $settings['r2_url'],
                ]);
                $disk = 'r2';
            } else {
                $disk = 'public';
            }

            // Upload file
            $filePath = $file->storeAs('submitted_documents', $fileName, $disk);

            // Extract text from PDF
            $extractedText = $this->extractTextFromPdf($file);

            // Create document record
            $document = SubmittedDocument::create([
                'user_id' => $user->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'title' => $request->title ?? $file->getClientOriginalName(),
                'description' => $request->description,
                'extracted_text' => $extractedText,
            ]);

            // Index document for RAG (async in production)
            $this->advancedRag->indexDocument($document);

            // Increment AI usage
            $subscription->incrementAIAnalysis();

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded and analyzed successfully',
                'data' => [
                    'document' => [
                        'id' => $document->id,
                        'title' => $document->title,
                        'file_name' => $document->file_name,
                        'file_size' => $document->formatted_file_size,
                        'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                    ],
                    'quotas' => [
                        'ai_analyses_used' => $subscription->ai_analyses_used,
                        'ai_analyses_limit' => $subscription->plan->ai_analyses_limit,
                        'remaining' => $subscription->plan->ai_analyses_limit - $subscription->ai_analyses_used,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document upload failed',
                'error' => $e->getMessage(),
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
                return [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'file_name' => $doc->file_name,
                    'file_size' => $doc->formatted_file_size,
                    'description' => $doc->description,
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
            'query' => 'required|string|min:3',
            'category_id' => 'nullable|integer|exists:legal_categories,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $subscription = $user->mobileAppSubscription()->where('status', 'active')->first();

        if (!$subscription || !$subscription->canSearch()) {
            return response()->json([
                'success' => false,
                'message' => 'Search quota exceeded. Please upgrade your plan.',
            ], 403);
        }

        try {
            $query = $request->query;
            $categoryId = $request->category_id;
            $limit = $request->limit ?? 10;

            $documentsQuery = LegalDocument::whereRaw(
                "MATCH(title, description, extracted_text) AGAINST(? IN NATURAL LANGUAGE MODE)",
                [$query]
            )
            ->select([
                'id',
                'title',
                'description',
                'category_id',
                'file_name',
                'file_size',
                'created_at',
            ])
            ->with('category:id,name');

            if ($categoryId) {
                $documentsQuery->where('category_id', $categoryId);
            }

            $documents = $documentsQuery
                ->orderByRaw("MATCH(title, description, extracted_text) AGAINST('{$query}' IN NATURAL LANGUAGE MODE) DESC")
                ->limit($limit)
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'description' => $doc->description,
                        'category' => $doc->category->name ?? 'N/A',
                        'file_name' => $doc->file_name,
                        'file_size' => $doc->formatted_file_size,
                        'created_at' => $doc->created_at->format('Y-m-d'),
                    ];
                });

            // Increment search usage
            $subscription->incrementSearch();

            return response()->json([
                'success' => true,
                'data' => [
                    'documents' => $documents,
                    'total' => $documents->count(),
                    'quotas' => [
                        'searches_used' => $subscription->searches_used,
                        'searches_limit' => $subscription->plan->searches_limit,
                        'remaining' => $subscription->plan->searches_limit - $subscription->searches_used,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
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
        $subscription = $user->mobileAppSubscription()->where('status', 'active')->first();

        if (!$subscription || !$subscription->canDownloadPDF()) {
            return response()->json([
                'success' => false,
                'message' => 'PDF download quota exceeded. Please upgrade your plan.',
            ], 403);
        }

        $document = LegalDocument::find($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        // Get file URL
        $url = Utility::get_file($document->file_path);

        if (empty($url)) {
            return response()->json([
                'success' => false,
                'message' => 'File not accessible',
            ], 404);
        }

        // Record download
        DocumentDownload::create([
            'user_id' => $user->id,
            'legal_document_id' => $documentId,
        ]);

        // Increment download usage
        $subscription->incrementPDFDownload();

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $url,
                'file_name' => $document->file_name,
                'quotas' => [
                    'pdf_downloads_used' => $subscription->pdf_downloads_used,
                    'pdf_downloads_limit' => $subscription->plan->pdf_downloads_limit,
                    'remaining' => $subscription->plan->pdf_downloads_limit - $subscription->pdf_downloads_used,
                ],
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

        // Delete from storage
        $settings = Utility::settings();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        $disk = ($storageSetting === 'r2') ? 'r2' : 'public';

        if ($storageSetting === 'r2') {
            config([
                'filesystems.disks.r2.key' => $settings['r2_key'],
                'filesystems.disks.r2.secret' => $settings['r2_secret'],
                'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
                'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
                'filesystems.disks.r2.url' => $settings['r2_url'],
            ]);
        }

        Storage::disk($disk)->delete($document->file_path);

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
     * @return string
     */
    private function extractTextFromPdf($file): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();
            
            // Clean text
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            return $text;
        } catch (\Exception $e) {
            \Log::error("PDF text extraction failed: " . $e->getMessage());
            return '';
        }
    }
}
