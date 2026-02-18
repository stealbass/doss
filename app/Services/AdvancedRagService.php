<?php

namespace App\Services;

use App\Models\SubmittedDocument;
use App\Models\LegalDocument;
use App\Models\DocumentTemplate;
use App\Models\FiscalSocialResource;
use App\Models\MobileAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Advanced RAG Service
 * 
 * Uses OpenAI Embeddings + Pinecone Vector Database
 * For semantic search in user-submitted documents
 */
class AdvancedRagService
{
    private string $openaiApiKey;
    private string $pineconeApiKey;
    private string $pineconeEnvironment;
    private string $pineconeHost;
    private string $pineconeIndex;
    private bool $pineconeVerify;
    private string $embeddingModel = 'text-embedding-3-small';

    public function __construct()
    {
        $mobileSettings = MobileAppSetting::first();

        // Prioritize MobileAppSetting over .env configuration
        $this->openaiApiKey = $mobileSettings?->openai_api_key
            ?: config('services.openai.api_key', env('OPENAI_API_KEY', ''));

        $this->pineconeApiKey = $mobileSettings?->pinecone_api_key
            ?? env('PINECONE_API_KEY', '');

        $this->pineconeEnvironment = $mobileSettings?->pinecone_environment
            ?? env('PINECONE_ENVIRONMENT', 'gcp-starter');

        $this->pineconeIndex = $mobileSettings?->pinecone_index
            ?? env('PINECONE_INDEX', 'dossy-legal-docs');

        $this->pineconeHost = $this->sanitizePineconeHost(
            $mobileSettings?->pinecone_host ?? env('PINECONE_HOST')
        );

        $this->pineconeVerify = filter_var(
            $mobileSettings?->pinecone_verify_ssl ?? env('PINECONE_VERIFY_SSL', true),
            FILTER_VALIDATE_BOOL
        );

        // Fallback host construction when none provided
        if (empty($this->pineconeHost)) {
            $this->pineconeHost = $this->sanitizePineconeHost(
                "{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io"
            );
        }
    }

    /**
     * Normalize Pinecone host (strip protocol/paths) to avoid malformed URLs
     */
    private function sanitizePineconeHost(?string $host): string
    {
        if (empty($host)) {
            return '';
        }

        // If a full URL is provided, extract the host portion
        if (str_starts_with($host, 'http://') || str_starts_with($host, 'https://')) {
            $parsed = parse_url($host);
            $host = $parsed['host'] ?? $host;
        }

        // Remove any trailing slashes
        return trim($host, '/');
    }

    /**
     * Index a submitted document (create embeddings and store in Pinecone)
     * 
     * @param SubmittedDocument $document
     * @return bool Success status
     */
    public function indexDocument(SubmittedDocument $document): bool
    {
        try {
            // Check if document has extracted text
            if (empty($document->extracted_text)) {
                Log::warning("Document {$document->id} has no extracted text for indexing");
                return false;
            }

            // Split text into chunks
            $chunks = $this->chunkText($document->extracted_text, 800);

            $vectors = [];
            $fileName = $document->original_filename
                ?? $document->stored_filename
                ?? $document->storage_path
                ?? 'Document';
            $fileName = trim((string) $fileName);
            if ($fileName === '') {
                $fileName = 'Document';
            }
            foreach ($chunks as $index => $chunk) {
                // Generate embedding for this chunk
                $embedding = $this->generateEmbedding($chunk);

                if (!$embedding) {
                    Log::error("Failed to generate embedding for document {$document->id}, chunk {$index}");
                    continue;
                }

                // Prepare vector for Pinecone
                $vectors[] = [
                    'id' => "doc_{$document->id}_chunk_{$index}",
                    'values' => $embedding,
                    'metadata' => [
                        'document_id' => $document->id,
                        'user_id' => $document->user_id,
                        'chunk_index' => $index,
                        'text' => $chunk,
                        'file_name' => $fileName,
                        'document_title' => $fileName,
                        'created_at' => $document->created_at->toIso8601String(),
                    ]
                ];
            }

            // Upsert vectors to Pinecone
            if (!empty($vectors)) {
                return $this->upsertVectors($vectors);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error indexing document {$document->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Index a legal library document
     */
    public function indexLegalDocument(LegalDocument $document): bool
    {
        try {
            if (empty($document->extracted_text)) {
                Log::warning("LegalDocument {$document->id} has no extracted text for indexing");
                return false;
            }

            $chunks = $this->chunkText($document->extracted_text, 800);
            $vectors = [];

            foreach ($chunks as $index => $chunk) {
                $embedding = $this->generateEmbedding($chunk);
                if (!$embedding) {
                    Log::error("Failed to generate embedding for legal document {$document->id}, chunk {$index}");
                    continue;
                }

                $vectors[] = [
                    'id' => "legal_{$document->id}_chunk_{$index}",
                    'values' => $embedding,
                    'metadata' => [
                        'document_id' => $document->id,
                        'source' => 'legal',
                        'chunk_index' => $index,
                        'text' => $chunk,
                        'file_name' => $document->file_name,
                        'document_title' => $document->title ?? $document->file_name,
                        'country' => $document->country ?? '',
                        'created_at' => optional($document->created_at)->toIso8601String(),
                    ],
                ];
            }

            return !empty($vectors) ? $this->upsertVectors($vectors) : true;
        } catch (\Exception $e) {
            Log::error("Error indexing legal document {$document->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Index a document template
     */
    public function indexTemplate(DocumentTemplate $template): bool
    {
        try {
            if (empty($template->extracted_text)) {
                Log::warning("DocumentTemplate {$template->id} has no extracted text for indexing");
                return false;
            }

            $chunks = $this->chunkText($template->extracted_text, 800);
            $vectors = [];

            foreach ($chunks as $index => $chunk) {
                $embedding = $this->generateEmbedding($chunk);
                if (!$embedding) {
                    Log::error("Failed to generate embedding for template {$template->id}, chunk {$index}");
                    continue;
                }

                $vectors[] = [
                    'id' => "template_{$template->id}_chunk_{$index}",
                    'values' => $embedding,
                    'metadata' => [
                        'document_id' => $template->id,
                        'source' => 'template',
                        'chunk_index' => $index,
                        'text' => $chunk,
                        'file_name' => $template->file_name,
                        'document_title' => $template->name ?? $template->file_name,
                        'country' => $template->country ?? '',
                        'template_type' => $template->template_type ?? '',
                        'created_at' => optional($template->created_at)->toIso8601String(),
                    ],
                ];
            }

            return !empty($vectors) ? $this->upsertVectors($vectors) : true;
        } catch (\Exception $e) {
            Log::error("Error indexing template {$template->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Index a fiscal/social resource
     */
    public function indexFiscalResource(FiscalSocialResource $resource): bool
    {
        try {
            if (empty($resource->extracted_text)) {
                Log::warning("FiscalResource {$resource->id} has no extracted text for indexing");
                return false;
            }

            $chunks = $this->chunkText($resource->extracted_text, 800);
            $vectors = [];

            foreach ($chunks as $index => $chunk) {
                $embedding = $this->generateEmbedding($chunk);
                if (!$embedding) {
                    Log::error("Failed to generate embedding for fiscal resource {$resource->id}, chunk {$index}");
                    continue;
                }

                $vectors[] = [
                    'id' => "fiscal_{$resource->id}_chunk_{$index}",
                    'values' => $embedding,
                    'metadata' => [
                        'document_id' => $resource->id,
                        'source' => 'fiscal',
                        'chunk_index' => $index,
                        'text' => $chunk,
                        'file_name' => $resource->file_name,
                        'document_title' => $resource->name ?? $resource->file_name,
                        'country' => $resource->country ?? '',
                        'year' => $resource->year ?? 0,
                        'resource_type' => $resource->resource_type ?? '',
                        'created_at' => optional($resource->created_at)->toIso8601String(),
                    ],
                ];
            }

            return !empty($vectors) ? $this->upsertVectors($vectors) : true;
        } catch (\Exception $e) {
            Log::error("Error indexing fiscal resource {$resource->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search similar documents using semantic search
     * 
     * @param string $query Search query
     * @param int $userId Filter by user ID
     * @param int $topK Number of results
     * @return array Search results
     */
    public function search(string $query, int $userId, int $topK = 20): array
    {
        try {
            // Generate embedding for the query
            $queryEmbedding = $this->generateEmbedding($query);

            if (!$queryEmbedding) {
                Log::error("Failed to generate embedding for query");
                return [];
            }

            // Query Pinecone
            $results = $this->queryPinecone($queryEmbedding, $userId, $topK);

            // Format results
            return $this->formatResults($results);
        } catch (\Exception $e) {
            Log::error("Error searching documents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search specific documents using semantic search
     * 
     * @param string $query Search query
     * @param int $userId Filter by user ID
     * @param array $documentIds Filter by specific document IDs
     * @param int $topK Number of results
     * @return array Search results
     */
    public function searchSpecificDocuments(string $query, int $userId, array $documentIds, int $topK = 10): array
    {
        try {
            // Generate embedding for the query
            $queryEmbedding = $this->generateEmbedding($query);

            if (!$queryEmbedding) {
                Log::error("Failed to generate embedding for query");
                return [];
            }

            // Query Pinecone with document_id filter
            $results = $this->queryPineconeWithDocuments($queryEmbedding, $userId, $documentIds, $topK);

            // Format results
            return $this->formatResults($results);
        } catch (\Exception $e) {
            Log::error("Error searching specific documents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get context from search results for RAG
     * 
     * @param string $query Search query
     * @param int $userId User ID
     * @param int $maxTokens Maximum tokens for context
     * @return string Combined context
     */
    public function getContext(string $query, int $userId, int $maxTokens = 2000): string
    {
        $results = $this->search($query, $userId, 5);

        if (empty($results)) {
            return '';
        }

        $context = "Documents personnels pertinents:\n\n";
        $currentTokens = 0;

        foreach ($results as $result) {
            $docContext = $this->buildDocumentContext($result);
            $docTokens = $this->estimateTokens($docContext);

            if ($currentTokens + $docTokens > $maxTokens) {
                break;
            }

            $context .= $docContext . "\n\n---\n\n";
            $currentTokens += $docTokens;
        }

        return $context;
    }

    /**
     * Delete document from Pinecone index
     * 
     * @param int $documentId
     * @return bool Success status
     */
    public function deleteDocument(int $documentId): bool
    {
        try {
            // Delete all vectors for this document
            return $this->deleteVectors("doc_{$documentId}");
        } catch (\Exception $e) {
            Log::error("Error deleting document {$documentId} from index: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete template vectors from Pinecone
     */
    public function deleteTemplate(int $templateId): bool
    {
        try {
            return $this->deleteVectors("template_{$templateId}");
        } catch (\Exception $e) {
            Log::error("Error deleting template {$templateId} from index: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete fiscal/social resource vectors from Pinecone
     */
    public function deleteFiscalResource(int $resourceId): bool
    {
        try {
            return $this->deleteVectors("fiscal_{$resourceId}");
        } catch (\Exception $e) {
            Log::error("Error deleting fiscal resource {$resourceId} from index: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate OpenAI embedding for text
     * 
     * @param string $text
     * @return array|null Embedding vector
     */
    private function generateEmbedding(string $text): ?array
    {
        if (empty($this->openaiApiKey)) {
            Log::error("OpenAI API key not configured");
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->embeddingModel,
                'input' => $text,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }

            Log::error("OpenAI API error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Error generating embedding: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upsert vectors to Pinecone
     * 
     * @param array $vectors
     * @return bool Success status
     */
    private function upsertVectors(array $vectors): bool
    {
        if (empty($this->pineconeApiKey)) {
            Log::error("Pinecone API key not configured");
            return false;
        }

        try {
            // Ensure proper host format (no trailing slashes)
            $host = trim($this->pineconeHost, '/');
            if (empty($host)) {
                $host = "{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io";
            }
            
            $url = "https://{$host}/vectors/upsert";

            // Sanitize metadata to avoid null values (Pinecone rejects nulls)
            $vectors = array_map(function ($vector) {
                if (!isset($vector['metadata']) || !is_array($vector['metadata'])) {
                    return $vector;
                }

                foreach ($vector['metadata'] as $key => $value) {
                    if ($value === null) {
                        $vector['metadata'][$key] = '';
                    }
                }

                return $vector;
            }, $vectors);

            // Build curl options with SSL/TLS workaround
            $options = [
                'timeout' => 30,
                'connect_timeout' => 10,
                'allow_redirects' => true,
            ];
            
            // Disable SSL verification only if explicitly disabled
            // to handle TLS handshake failures
            if (!$this->pineconeVerify) {
                $options['verify'] = false;
            } else {
                // Use default verification
                $options['verify'] = true;
            }

            Log::info("Pinecone upsert", [
                'url' => $url,
                'vector_count' => count($vectors),
                'ssl_verify' => $this->pineconeVerify,
            ]);

            $response = Http::withOptions($options)->withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'vectors' => $vectors,
            ]);

            if ($response->successful()) {
                Log::info("Pinecone upsert successful", [
                    'vector_count' => count($vectors),
                ]);
                return true;
            }

            Log::error("Pinecone upsert error", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Error upserting to Pinecone: " . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Query Pinecone for similar vectors
     * 
     * @param array $queryVector
     * @param int $userId
     * @param int $topK
     * @return array Results
     */
    private function queryPinecone(array $queryVector, int $userId, int $topK): array
    {
        if (empty($this->pineconeApiKey)) {
            Log::error("Pinecone API key not configured");
            return [];
        }

        try {
            // Ensure proper host format
            $host = trim($this->pineconeHost, '/');
            if (empty($host)) {
                $host = "{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io";
            }
            
            $url = "https://{$host}/query";

            // Build curl options with SSL/TLS workaround
            $options = [
                'timeout' => 30,
                'connect_timeout' => 10,
                'allow_redirects' => true,
            ];
            
            // Disable SSL verification only if explicitly disabled
            if (!$this->pineconeVerify) {
                $options['verify'] = false;
            } else {
                $options['verify'] = true;
            }

            Log::info("Pinecone query", [
                'url' => $url,
                'top_k' => $topK,
                'ssl_verify' => $this->pineconeVerify,
            ]);

            $response = Http::withOptions($options)->withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'vector' => $queryVector,
                'topK' => $topK,
                'includeMetadata' => true,
                'filter' => [
                    'user_id' => ['$eq' => $userId]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Pinecone query successful", [
                    'matches' => count($data['matches'] ?? []),
                ]);
                return $data['matches'] ?? [];
            }

            Log::error("Pinecone query error", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error("Error querying Pinecone: " . $e->getMessage(), [
                'exception' => get_class($e),
            ]);
            return [];
        }
    }

    /**
     * Query Pinecone for similar vectors with document filter
     * 
     * @param array $queryVector
     * @param int $userId
     * @param array $documentIds
     * @param int $topK
     * @return array Results
     */
    private function queryPineconeWithDocuments(array $queryVector, int $userId, array $documentIds, int $topK): array
    {
        if (empty($this->pineconeApiKey)) {
            Log::error("Pinecone API key not configured");
            return [];
        }

        try {
            // Ensure proper host format
            $host = trim($this->pineconeHost, '/');
            if (empty($host)) {
                $host = "{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io";
            }
            
            $url = "https://{$host}/query";

            $response = Http::withOptions([
                'verify' => $this->pineconeVerify,
            ])->withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'vector' => $queryVector,
                'topK' => $topK,
                'includeMetadata' => true,
                'filter' => [
                    '$and' => [
                        ['user_id' => ['$eq' => $userId]],
                        ['document_id' => ['$in' => $documentIds]]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['matches'] ?? [];
            }

            Log::error("Pinecone query with documents error: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Error querying Pinecone with documents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete vectors from Pinecone by prefix
     * 
     * @param string $prefix ID prefix
     * @return bool Success status
     */
    private function deleteVectors(string $prefix): bool
    {
        if (empty($this->pineconeApiKey)) {
            return false;
        }

        try {
            // Ensure proper host format
            $host = trim($this->pineconeHost, '/');
            if (empty($host)) {
                $host = "{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io";
            }
            
            $url = "https://{$host}/vectors/delete";

            // Build curl options with better SSL handling
            $options = [
                'verify' => $this->pineconeVerify,
                'timeout' => 30,  // Prevent hanging
            ];
            
            // If SSL verification disabled, disable certificate pinning and cipher restrictions
            if (!$this->pineconeVerify) {
                $options['allow_redirects'] = true;
            }

            $response = Http::withOptions($options)->withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])->delete($url, [
                'deleteAll' => false,
                'filter' => [
                    'id' => ['$regex' => "^{$prefix}_"]
                ]
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error deleting from Pinecone: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Chunk text into smaller pieces
     * 
     * @param string $text
     * @param int $maxTokens
     * @return array Text chunks
     */
    private function chunkText(string $text, int $maxTokens = 500): array
    {
        // Simple chunking by words (can be improved with sentence splitting)
        $words = explode(' ', $text);
        $chunks = [];
        $currentChunk = [];
        $currentTokens = 0;

        foreach ($words as $word) {
            $wordTokens = $this->estimateTokens($word);

            if ($currentTokens + $wordTokens > $maxTokens && !empty($currentChunk)) {
                $chunks[] = implode(' ', $currentChunk);
                $currentChunk = [];
                $currentTokens = 0;
            }

            $currentChunk[] = $word;
            $currentTokens += $wordTokens;
        }

        if (!empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }

        return $chunks;
    }

    /**
     * Format Pinecone results
     * 
     * @param array $results
     * @return array Formatted results
     */
    private function formatResults(array $results): array
    {
        return array_map(function ($match) {
            $meta = $match['metadata'] ?? [];
            return [
                'document_id' => $meta['document_id'] ?? null,
                'chunk_index' => $meta['chunk_index'] ?? 0,
                'text' => $meta['text'] ?? '',
                'file_name' => $meta['file_name'] ?? '',
                'document_title' => $meta['document_title'] ?? ($meta['file_name'] ?? ''),
                'source' => $meta['source'] ?? null,
                'country' => $meta['country'] ?? null,
                'template_type' => $meta['template_type'] ?? null,
                'resource_type' => $meta['resource_type'] ?? null,
                'year' => $meta['year'] ?? null,
                'score' => $match['score'] ?? 0,
                'created_at' => $meta['created_at'] ?? null,
                // Keep full metadata for downstream consumers (contexts/sources)
                'metadata' => $meta,
            ];
        }, $results);
    }

    /**
     * Build context string for a result
     * 
     * @param array $result
     * @return string Context
     */
    private function buildDocumentContext(array $result): string
    {
        $context = "Document: {$result['file_name']}\n";
        $context .= "Pertinence: " . round($result['score'] * 100, 2) . "%\n";
        $context .= "Contenu: {$result['text']}\n";

        return $context;
    }

    /**
     * Estimate token count
     * 
     * @param string $text
     * @return int Estimated tokens
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get index statistics
     * 
     * @param int $userId
     * @return array Statistics
     */
    public function getIndexStats(int $userId): array
    {
        $totalDocs = SubmittedDocument::where('user_id', $userId)->count();
        $indexedDocs = SubmittedDocument::where('user_id', $userId)
            ->whereNotNull('extracted_text')
            ->where('extracted_text', '!=', '')
            ->count();

        return [
            'total_documents' => $totalDocs,
            'indexed_documents' => $indexedDocs,
            'index_coverage' => $totalDocs > 0 ? round(($indexedDocs / $totalDocs) * 100, 2) : 0,
        ];
    }

    /**
     * Search in user-submitted documents with extracted text (NO PINECONE)
     * Local search using extracted_text field
     * 
     * @param string $query Search query
     * @param int $userId Filter by user ID
     * @param int $topK Number of results
     * @param array|null $documentIds Limit search to specific documents
     * @return array Search results with context
     */
    public function searchUserDocuments(string $query, int $userId, int $topK = 20, ?array $documentIds = null): array
    {
        try {
            Log::info("Searching user documents", [
                'user_id' => $userId,
                'query' => $query,
                'document_ids' => $documentIds,
            ]);

            // Get completed submitted documents for this user
            $documents = SubmittedDocument::where('user_id', $userId)
                ->where('processing_status', 'completed')
                ->where('extracted_text', '!=', null)
                ->when(!empty($documentIds), function ($query) use ($documentIds) {
                    $query->whereIn('id', $documentIds);
                })
                ->orderBy('processed_at', 'desc')
                ->get();

            if ($documents->isEmpty()) {
                Log::info("No processed documents found for user", ['user_id' => $userId]);
                return [];
            }

            // Score documents based on query match
            $scoredDocs = [];
            $queryLower = strtolower($query);
            
            foreach ($documents as $doc) {
                if (empty($doc->extracted_text)) {
                    continue;
                }

                // Simple relevance scoring (can be enhanced with TF-IDF)
                $docTextLower = strtolower($doc->extracted_text);
                $score = $this->calculateRelevanceScore($queryLower, $docTextLower);

                if ($score > 0) {
                    $scoredDocs[] = [
                        'id' => $doc->id,
                        'filename' => $doc->original_filename,
                        'score' => $score,
                        'document' => $doc,
                    ];
                }
            }

            // Sort by score descending
            usort($scoredDocs, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Get top K results
            $topResults = array_slice($scoredDocs, 0, $topK);

            // Extract relevant context from each document
            $results = [];
            foreach ($topResults as $item) {
                $doc = $item['document'];
                $context = $this->extractRelevantContext(
                    $doc->extracted_text,
                    $queryLower,
                    1500 // contexte élargi pour tableaux/sections chiffrées
                );

                $results[] = [
                    'id' => $doc->id,
                    'type' => 'user_document',
                    'filename' => $doc->original_filename,
                    'mime_type' => $doc->mime_type,
                    'context' => $context,
                    'score' => $item['score'],
                    'metadata' => $doc->metadata,
                    'processed_at' => $doc->processed_at,
                ];
            }

            Log::info("User documents search completed", [
                'user_id' => $userId,
                'results_count' => count($results)
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error("Error searching user documents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate relevance score for a document
     * Based on keyword matches in text
     * 
     * @param string $query Query in lowercase
     * @param string $text Document text in lowercase
     * @return float Relevance score (0-1)
     */
    private function calculateRelevanceScore(string $query, string $text): float
    {
        $queryWords = array_filter(explode(' ', $query));
        
        if (empty($queryWords)) {
            return 0;
        }

        $matches = 0;
        foreach ($queryWords as $word) {
            if (strlen($word) > 2 && stripos($text, $word) !== false) {
                $matches++;
            }
        }

        return $matches / count($queryWords);
    }

    /**
     * Extract relevant context around query matches
     * 
     * @param string $text Full document text
     * @param string $query Query string
     * @param int $contextLength Number of characters around match
     * @return string Relevant context excerpt
     */
    private function extractRelevantContext(string $text, string $query, int $contextLength = 500): string
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }

        // Fast keyword window (better for tables and numeric fields)
        $queryWords = array_filter(explode(' ', $query));
        $textLower = strtolower($text);
        foreach ($queryWords as $word) {
            if (strlen($word) > 2) {
                $pos = stripos($textLower, $word);
                if ($pos !== false) {
                    $start = max(0, $pos - (int) ($contextLength / 2));
                    $snippet = substr($text, $start, $contextLength);
                    return trim($snippet);
                }
            }
        }

        // Split into sentences
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $relevantSentences = [];

        $queryWords = array_filter(explode(' ', $query));

        foreach ($sentences as $sentence) {
            $sentenceLower = strtolower(trim($sentence));
            
            // Check if sentence contains query words
            $matchCount = 0;
            foreach ($queryWords as $word) {
                if (strlen($word) > 2 && stripos($sentenceLower, $word) !== false) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $relevantSentences[] = trim($sentence);
            }
        }

        // Join top 3 most relevant sentences
        $context = implode(' ', array_slice($relevantSentences, 0, 3));
        
        if (empty($context)) {
            // Fallback: return first N characters
            $context = substr($text, 0, $contextLength);
        }

        return trim($context);
    }

    /**
     * Combined search: Pinecone + User Documents
     * Searches both semantic index and user-submitted documents
     * 
     * @param string $query Search query
     * @param int $userId User ID
     * @param int $topK Number of results
     * @return array Combined search results
     */
    public function searchCombined(string $query, int $userId, int $topK = 5): array
    {
        try {
            Log::info("Combined search initiated", ['user_id' => $userId, 'query' => $query]);

            // Search user documents first (faster, no API calls)
            $userDocResults = $this->searchUserDocuments($query, $userId, $topK, null);

            // Search Pinecone (if configured)
            $pineconeResults = [];
            if (!empty($this->pineconeApiKey)) {
                try {
                    $pineconeResults = $this->searchSpecificDocuments($query, $userId, [], $topK);
                } catch (\Exception $e) {
                    Log::warning("Pinecone search failed, using user documents only: " . $e->getMessage());
                }
            }

            // Merge and deduplicate results
            $allResults = array_merge($userDocResults, $pineconeResults);
            
            // Limit to topK
            $finalResults = array_slice($allResults, 0, $topK);

            return $finalResults;
        } catch (\Exception $e) {
            Log::error("Error in combined search: " . $e->getMessage());
            // Fallback to user documents only
            return $this->searchUserDocuments($query, $userId, $topK, null);
        }
    }

    /**
     * Search library documents (legal, templates, fiscal) via Pinecone
     * 
     * @param string $query Search query
     * @param string $country Country filter
     * @param array $sources Sources to search: legal, template, fiscal
     * @param int $topK Number of results per source
     * @return array Search results grouped by source
     */
    public function searchLibraries(string $query, string $country = null, array $sources = ['legal', 'template', 'fiscal'], int $topK = 5): array
    {
        try {
            if (empty($this->pineconeApiKey)) {
                Log::warning("Pinecone not configured, using database fallback for library search");
                return $this->searchLibrariesFallback($query, $country, $sources, $topK);
            }

            $queryEmbedding = $this->generateEmbedding($query);
            if (!$queryEmbedding) {
                Log::error("Failed to generate embedding for library search, using fallback");
                return $this->searchLibrariesFallback($query, $country, $sources, $topK);
            }

            $allResults = [];
            
            foreach ($sources as $source) {
                try {
                    $results = $this->queryPineconeBySource($queryEmbedding, $source, $country, $topK);
                    if (!empty($results)) {
                        $allResults[$source] = $this->formatResults($results);
                    }
                } catch (\Exception $e) {
                    Log::warning("Library search failed for source {$source}, using fallback: " . $e->getMessage());
                    // Fallback to database search for this source
                    $fallbackResults = $this->searchLibrariesFallback($query, $country, [$source], $topK);
                    if (!empty($fallbackResults[$source])) {
                        $allResults[$source] = $fallbackResults[$source];
                    }
                }
            }

            // If Pinecone failed completely, use fallback
            if (empty($allResults)) {
                Log::info("Pinecone returned no results, using database fallback");
                return $this->searchLibrariesFallback($query, $country, $sources, $topK);
            }

            return $allResults;
        } catch (\Exception $e) {
            Log::error("Error in library search: " . $e->getMessage());
            // Fallback to database search
            return $this->searchLibrariesFallback($query, $country, $sources, $topK);
        }
    }

    /**
     * Fallback search using database when Pinecone is unavailable
     * 
     * @param string $query Search query
     * @param string $country Country filter
     * @param array $sources Source types
     * @param int $topK Number of results
     * @return array Results
     */
    private function searchLibrariesFallback(string $query, string $country = null, array $sources = ['legal', 'template', 'fiscal'], int $topK = 5): array
    {
        $allResults = [];
        $keywords = $this->extractSearchKeywords($query);

        // Intent: detect employment/licenciement context to filter unrelated legal docs (e.g., foncier/notaires)
        $laborTerms = ['licenciement','licencier','licenciements','travail','travailleurs','salari','employe','employés','employées','contrat de travail','code du travail'];
        $hasLaborIntent = $this->containsAny($query, $laborTerms);
        
        try {
            // Search in legal documents
            if (in_array('legal', $sources)) {
                $legalResults = DB::table('legal_documents')
                    ->where(function($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            if (strlen($keyword) > 2) { // Skip short words
                                $q->orWhere('title', 'LIKE', "%{$keyword}%")
                                  ->orWhere('description', 'LIKE', "%{$keyword}%")
                                  ->orWhere('extracted_text', 'LIKE', "%{$keyword}%");
                            }
                        }
                    });
                    
                if ($country) {
                    $legalResults->where('country', $country);
                }
                
                $legalDocs = $legalResults->limit($topK)->get();
                
                // Fallback: if country filter returns zero, retry without country
                if ($legalDocs->isEmpty() && $country) {
                    $legalResultsNoCountry = DB::table('legal_documents')
                        ->where(function($q) use ($keywords) {
                            foreach ($keywords as $keyword) {
                                if (strlen($keyword) > 2) {
                                    $q->orWhere('title', 'LIKE', "%{$keyword}%")
                                      ->orWhere('description', 'LIKE', "%{$keyword}%")
                                      ->orWhere('extracted_text', 'LIKE', "%{$keyword}%");
                                }
                            }
                        });
                    $legalDocs = $legalResultsNoCountry->limit($topK)->get();
                    Log::info("Legal documents fallback (no country)", [
                        'query' => $query,
                        'keywords' => $keywords,
                        'country' => $country,
                        'results_count' => $legalDocs->count(),
                        'sql' => $legalResultsNoCountry->toSql(),
                    ]);
                } else {
                    Log::info("Legal documents fallback search", [
                        'query' => $query,
                        'keywords' => $keywords,
                        'country' => $country,
                        'results_count' => $legalDocs->count(),
                        'sql' => $legalResults->toSql(),
                    ]);
                }
                
                if ($legalDocs->isNotEmpty()) {
                    $allResults['legal'] = [];
                    foreach ($legalDocs as $doc) {
                        $text = $doc->extracted_text ?? $doc->description ?? '';
                        $description = $doc->description ?? '';
                        $title = $doc->title ?? '';
                        
                        // Extract filename from file_path
                        $fileName = $title; // Default to title
                        if (!empty($doc->file_path)) {
                            $pathParts = pathinfo($doc->file_path);
                            $fileName = $pathParts['filename'] ?? $title;
                        }
                        
                        // Priority 1: Calculate TF-IDF relevance score instead of static 0.75
                        $relevanceScore = $this->calculateTfIdfScore($keywords, $title, $description, $text);

                        // If intent is labor/licenciement, prefer documents that mention labor terms
                        // but never drop ALL results: only skip after at least one labor-related doc is kept
                        if ($hasLaborIntent
                            && !$this->containsAny($title . ' ' . $description . ' ' . $text, $laborTerms)
                            && !empty($allResults['legal'])) {
                            continue;
                        }
                        
                        // Priority 2: Extract context by sentence relevance instead of substring
                        $extractedContext = $this->extractContextByRelevance($text, $keywords, 5);
                        
                        $allResults['legal'][] = [
                            'score' => $relevanceScore, // Dynamic TF-IDF score (Priority 1)
                            'metadata' => [
                                'document_id' => $doc->id,
                                'document_title' => $title,
                                'file_name' => $fileName,
                                'text' => $extractedContext, // Sentence-based extraction (Priority 2)
                                'country' => $doc->country,
                            ]
                        ];
                    }
                    
                    // Priority 1: Sort results by relevance score before selecting top-3
                    usort($allResults['legal'], fn($a, $b) => $b['score'] <=> $a['score']);
                    $allResults['legal'] = array_slice($allResults['legal'], 0, 3);
                }
            }
            
            // Search in fiscal resources
            if (in_array('fiscal', $sources)) {
                $fiscalResults = DB::table('fiscal_social_resources')
                    ->where(function($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            if (strlen($keyword) > 2) { // Skip short words
                                $q->orWhere('title', 'LIKE', "%{$keyword}%")
                                  ->orWhere('description', 'LIKE', "%{$keyword}%")
                                  ->orWhere('extracted_text', 'LIKE', "%{$keyword}%");
                            }
                        }
                    });
                    
                if ($country) {
                    $fiscalResults->where('country', $country);
                }
                
                $fiscalDocs = $fiscalResults->limit($topK)->get();
                
                // Fallback: if country filter returns zero, retry without country
                if ($fiscalDocs->isEmpty() && $country) {
                    $fiscalResultsNoCountry = DB::table('fiscal_social_resources')
                        ->where(function($q) use ($keywords) {
                            foreach ($keywords as $keyword) {
                                if (strlen($keyword) > 2) {
                                    $q->orWhere('title', 'LIKE', "%{$keyword}%")
                                      ->orWhere('description', 'LIKE', "%{$keyword}%")
                                      ->orWhere('extracted_text', 'LIKE', "%{$keyword}%");
                                }
                            }
                        });
                    $fiscalDocs = $fiscalResultsNoCountry->limit($topK)->get();
                    Log::info("Fiscal resources fallback (no country)", [
                        'query' => $query,
                        'keywords' => $keywords,
                        'country' => $country,
                        'results_count' => $fiscalDocs->count(),
                        'sql' => $fiscalResultsNoCountry->toSql(),
                    ]);
                } else {
                    Log::info("Fiscal resources fallback search", [
                        'query' => $query,
                        'keywords' => $keywords,
                        'country' => $country,
                        'results_count' => $fiscalDocs->count(),
                        'sql' => $fiscalResults->toSql(),
                    ]);
                }
                
                if ($fiscalDocs->isNotEmpty()) {
                    $allResults['fiscal'] = [];
                    foreach ($fiscalDocs as $doc) {
                        $text = $doc->extracted_text ?? $doc->description ?? '';
                        $description = $doc->description ?? '';
                        $title = $doc->name ?? '';
                        
                        // Extract filename from file_path
                        $fileName = $title; // Default to name
                        if (!empty($doc->file_path)) {
                            $pathParts = pathinfo($doc->file_path);
                            $fileName = $pathParts['filename'] ?? $title;
                        }
                        
                        // Priority 1: Calculate TF-IDF relevance score instead of static 0.75
                        $relevanceScore = $this->calculateTfIdfScore($keywords, $title, $description, $text);
                        
                        // Priority 2: Extract context by sentence relevance instead of substring
                        $extractedContext = $this->extractContextByRelevance($text, $keywords, 5);
                        
                        $allResults['fiscal'][] = [
                            'score' => $relevanceScore, // Dynamic TF-IDF score (Priority 1)
                            'metadata' => [
                                'document_id' => $doc->id,
                                'document_title' => $title,
                                'file_name' => $fileName,
                                'text' => $extractedContext, // Sentence-based extraction (Priority 2)
                                'country' => $doc->country,
                            ]
                        ];
                    }
                    
                    // Priority 1: Sort results by relevance score before selecting top-3
                    usort($allResults['fiscal'], fn($a, $b) => $b['score'] <=> $a['score']);
                    $allResults['fiscal'] = array_slice($allResults['fiscal'], 0, 3);
                }
            }
            
            // Search in templates
            if (in_array('template', $sources)) {
                $templateResults = DB::table('document_templates')
                    ->where(function($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            if (strlen($keyword) > 2) { // Skip short words
                                $q->orWhere('name', 'LIKE', "%{$keyword}%")
                                  ->orWhere('description', 'LIKE', "%{$keyword}%")
                                  ->orWhere('extracted_text', 'LIKE', "%{$keyword}%");
                            }
                        }
                    });
                    
                if ($country) {
                    $templateResults->where('country', $country);
                }
                
                $templateDocs = $templateResults->limit($topK)->get();
                
                // Fallback: if country filter returns zero, retry without country
                if ($templateDocs->isEmpty() && $country) {
                    $templateResultsNoCountry = DB::table('document_templates')
                        ->where(function($q) use ($keywords) {
                            foreach ($keywords as $keyword) {
                                if (strlen($keyword) > 2) {
                                    $q->orWhere('name', 'LIKE', "%{$keyword}%")
                                      ->orWhere('description', 'LIKE', "%{$keyword}%")
                                      ->orWhere('extracted_text', 'LIKE', "%{$keyword}%");
                                }
                            }
                        });
                    $templateDocs = $templateResultsNoCountry->limit($topK)->get();
                    Log::info("Template fallback (no country)", [
                        'query' => $query,
                        'keywords' => $keywords,
                        'country' => $country,
                        'results_count' => $templateDocs->count(),
                        'sql' => $templateResultsNoCountry->toSql(),
                    ]);
                }

                if ($templateDocs->isNotEmpty()) {
                    $allResults['template'] = [];
                    foreach ($templateDocs as $doc) {
                        $text = $doc->extracted_text ?? $doc->description ?? '';
                        $description = $doc->description ?? '';
                        $title = $doc->name ?? '';
                        
                        // Extract filename from file_path
                        $fileName = $title; // Default to name
                        if (!empty($doc->file_path)) {
                            $pathParts = pathinfo($doc->file_path);
                            $fileName = $pathParts['filename'] ?? $title;
                        }
                        
                        // Priority 1: Calculate TF-IDF relevance score instead of static 0.75
                        $relevanceScore = $this->calculateTfIdfScore($keywords, $title, $description, $text);
                        
                        // Priority 2: Extract context by sentence relevance instead of substring
                        $extractedContext = $this->extractContextByRelevance($text, $keywords, 5);
                        
                        $allResults['template'][] = [
                            'score' => $relevanceScore, // Dynamic TF-IDF score (Priority 1)
                            'metadata' => [
                                'document_id' => $doc->id,
                                'document_title' => $title,
                                'file_name' => $fileName,
                                'text' => $extractedContext, // Sentence-based extraction (Priority 2)
                                'country' => $doc->country,
                            ]
                        ];
                    }
                    
                    // Priority 1: Sort results by relevance score before selecting top-3
                    usort($allResults['template'], fn($a, $b) => $b['score'] <=> $a['score']);
                    $allResults['template'] = array_slice($allResults['template'], 0, 3);
                }
            }
            
            Log::info("Database fallback search completed", [
                'sources_found' => array_keys($allResults),
                'total_results' => array_sum(array_map('count', $allResults)),
            ]);
            
            return $allResults;
        } catch (\Exception $e) {
            Log::error("Database fallback search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate TF-IDF-based relevance score for a document
     * 
     * Weights: title (3x) > description (2x) > body (1x)
     * Returns normalized score between 0.5 and 1.0
     * 
     * @param array $keywords Search keywords
     * @param string $title Document title
     * @param string $description Document description
     * @param string $text Document text
     * @return float Relevance score (0.5-1.0)
     */
    private function calculateTfIdfScore(array $keywords, string $title, string $description, string $text): float
    {
        $score = 0;
        $maxPossibleScore = 0;
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) <= 2) {
                continue; // Skip short keywords like 'du', 'la', etc.
            }
            
            // Count occurrences in each field
            $titleMatches = substr_count(strtolower($title), strtolower($keyword));
            $descMatches = substr_count(strtolower($description), strtolower($keyword));
            $textMatches = substr_count(strtolower($text), strtolower($keyword));
            
            // Apply weights: title (3x) > description (2x) > body (1x)
            $keywordScore = ($titleMatches * 3) + ($descMatches * 2) + ($textMatches * 1);
            $score += $keywordScore;
            
            // Max score per keyword: 2 matches in title + 2 in desc + 5 in text
            $maxPossibleScore += (2 * 3) + (2 * 2) + (5 * 1);
        }
        
        // Normalize to 0.5-1.0 range (minimum 0.5 for database results)
        if ($maxPossibleScore === 0 || count($keywords) === 0) {
            return 0.75;
        }
        
        $normalizedScore = min($score / $maxPossibleScore, 1.0);
        return 0.5 + ($normalizedScore * 0.5);
    }

    /**
     * Extract meaningful keywords from a query, stripping stopwords and punctuation
     * to improve search precision and avoid returning the same generic sources.
     *
     * @param string $query Raw user query
     * @return array Filtered, unique keywords (lowercase)
     */
    private function extractSearchKeywords(string $query): array
    {
        // Basic French stopwords to drop noisy terms that cause generic matches
        $stopwords = [
            'les','des','pour','avec','dans','sur','une','que','qui','quoi','quel','quels','quelle','quelles',
            'dont','ainsi','est','sont','etes','ete','etre','avoir','aux','ses','leur','leurs','notre','votre',
            'vos','nos','du','de','la','le','au','a','par','en','et','ou','mais','ne','pas','plus','moins',
            'tout','tous','toutes','fait','faire','comme','selon','contre','vers','chez','entre','sans','sous',
            'depuis','lors','alors','ce','cette','ces','cet','ici','la','voici','voila','y','un','une','deux',
            'trois','quatre','cinq','six','sept','huit','neuf','dix'
        ];

        // Extract words (letters and digits), lowercase them
        preg_match_all('/[\p{L}\p{N}\-]+/u', mb_strtolower($query), $matches);
        $words = $matches[0] ?? [];

        $filtered = [];
        foreach ($words as $word) {
            // Remove hyphens only used for splitting (keep in codes like "ohada")
            $clean = trim($word, "-\t\n\r\0\x0B");
            if (strlen($clean) <= 2) {
                continue;
            }
            if (in_array($clean, $stopwords, true)) {
                continue;
            }
            $filtered[] = $clean;
        }

        // De-duplicate keywords to limit repeated OR conditions
        return array_values(array_unique($filtered));
    }

    /**
     * Check if haystack contains any of the needles (case-insensitive)
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        $lower = mb_strtolower($haystack);
        foreach ($needles as $needle) {
            if ($needle === '') {
                continue;
            }
            if (mb_stripos($lower, mb_strtolower($needle)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract context by relevance sentences (not character-based truncation)
     * 
     * Splits text by sentence boundaries and returns sentences matching keywords
     * Preserves complete legal clauses instead of cutting mid-sentence
     * 
     * @param string $text Document text
     * @param array $keywords Search keywords
     * @param int $maxSentences Maximum sentences to return (default 5)
     * @return string Extracted context
     */
    private function extractContextByRelevance(string $text, array $keywords, int $maxSentences = 5): string
    {
        if (empty($text) || empty($keywords)) {
            return substr($text, 0, 3000);
        }
        
        // Split by sentence boundaries
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($sentences) < 2) {
            return substr($text, 0, 3000);
        }
        
        $relevantSentences = [];
        
        foreach ($sentences as $sentence) {
            $matchCount = 0;
            
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 2 && stripos($sentence, $keyword) !== false) {
                    $matchCount++;
                }
            }
            
            if ($matchCount > 0 || preg_match('/^(Article|Section|Titre|Chapitre|Décret|Loi)/i', $sentence)) {
                $relevantSentences[] = [
                    'text' => trim($sentence),
                    'matches' => $matchCount
                ];
            }
        }
        
        if (empty($relevantSentences)) {
            $relevantSentences = array_slice(
                array_map(fn($s) => ['text' => trim($s), 'matches' => 0], $sentences),
                0,
                5
            );
        }
        
        usort($relevantSentences, fn($a, $b) => $b['matches'] <=> $a['matches']);
        
        $topSentences = array_slice($relevantSentences, 0, $maxSentences);
        
        $extractedText = implode(' ', array_column($topSentences, 'text'));
        
        return substr($extractedText, 0, 3000);
    }

    /**
     * Query Pinecone by source type (legal/template/fiscal)
     * 
     * @param array $queryVector
     * @param string $source Source type
     * @param string $country Country filter (optional)
     * @param int $topK
     * @return array Results
     */
    private function queryPineconeBySource(array $queryVector, string $source, string $country = null, int $topK = 5): array
    {
        if (empty($this->pineconeApiKey)) {
            return [];
        }

        try {
            // Ensure proper host format
            $host = trim($this->pineconeHost, '/');
            if (empty($host)) {
                $host = "{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io";
            }
            
            $url = "https://{$host}/query";

            // Build filter - first try without country filter since most docs don't have country set
            $filter = ['source' => ['$eq' => $source]];

            $response = Http::withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'vector' => $queryVector,
                'topK' => $topK * 2, // Get more results to filter by country later if needed
                'includeMetadata' => true,
                'filter' => $filter
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $matches = $data['matches'] ?? [];
                
                // If country filter requested, filter results by country in metadata
                // This is because many docs have empty country in Pinecone
                if ($country && !empty($matches)) {
                    $countryMatches = array_filter($matches, function($match) use ($country) {
                        $metaCountry = $match['metadata']['country'] ?? '';
                        return !empty($metaCountry) && strtoupper($metaCountry) === strtoupper($country);
                    });
                    
                    // If we found country-specific matches, use them; otherwise use all matches
                    if (!empty($countryMatches)) {
                        $matches = array_slice(array_values($countryMatches), 0, $topK);
                    } else {
                        $matches = array_slice($matches, 0, $topK);
                    }
                }
                
                return $matches;
            }

            Log::error("Pinecone query by source error: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Error querying Pinecone by source: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get library context for RAG (formatted for AI)
     * 
     * @param string $query Search query
     * @param string $country Country filter
     * @param int $maxTokens Max context tokens
     * @return array ['context' => string, 'sources' => array]
     */
    public function getLibraryContext(string $query, string $country = null, int $maxTokens = 2000, array $allowedSources = ['legal', 'template', 'fiscal']): array
    {
        $libraryResults = $this->searchLibraries($query, $country, $allowedSources, 3);
        
        if (empty($libraryResults)) {
            return ['context' => '', 'sources' => []];
        }

        $context = "=== BIBLIOTHÈQUES JURIDIQUES ET FISCALES ===\n\n";
        $sources = [];
        $currentTokens = 0;
        $seenKeys = [];

        foreach ($libraryResults as $sourceType => $results) {
            $sourceName = [
                'legal' => 'Documents Juridiques',
                'template' => 'Modèles de Documents',
                'fiscal' => 'Ressources Fiscales et Sociales'
            ][$sourceType] ?? $sourceType;

            $context .= "--- {$sourceName} ---\n\n";

            foreach ($results as $result) {
                $docId = $result['metadata']['document_id'] ?? null;
                $documentTitle = $result['metadata']['document_title'] ?? 'Document'; // Lisible title
                $fileName = $result['metadata']['file_name'] ?? 'Document'; // Technical filename
                $score = $result['score'] ?? 0;
                $text = $result['metadata']['text'] ?? '';

                // Deduplicate by source type + document id + filename
                $key = $sourceType . '|' . ($docId ?? 'null') . '|' . $fileName;
                if (isset($seenKeys[$key])) {
                    continue;
                }
                $seenKeys[$key] = true;
                
                $chunk = "📄 {$documentTitle} (Pertinence: " . number_format($score * 100, 1) . "%)\n";
                $chunk .= $text . "\n\n";
                
                $chunkTokens = $this->estimateTokens($chunk);
                
                if ($currentTokens + $chunkTokens > $maxTokens) {
                    break 2;
                }

                $context .= $chunk;
                $currentTokens += $chunkTokens;

                $sources[] = [
                    'id' => $docId,
                    'type' => $sourceType,
                    'title' => $documentTitle, // Display lisible title
                    'relevance_score' => $score,
                    'country' => $result['metadata']['country'] ?? null,
                ];
            }

            $context .= "\n";
        }

        return [
            'context' => $context,
            'sources' => $sources
        ];
    }
}
