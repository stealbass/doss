<?php

namespace App\Services;

use App\Models\SubmittedDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    private string $pineconeIndex;
    private string $embeddingModel = 'text-embedding-3-small';

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->pineconeApiKey = env('PINECONE_API_KEY', '');
        $this->pineconeEnvironment = env('PINECONE_ENVIRONMENT', 'us-east-1');
        $this->pineconeIndex = env('PINECONE_INDEX', 'dossy-documents');
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
            $chunks = $this->chunkText($document->extracted_text, 500);

            $vectors = [];
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
                        'file_name' => $document->file_name,
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
     * Search similar documents using semantic search
     * 
     * @param string $query Search query
     * @param int $userId Filter by user ID
     * @param int $topK Number of results
     * @return array Search results
     */
    public function search(string $query, int $userId, int $topK = 5): array
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
            $url = "https://{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io/vectors/upsert";

            $response = Http::withHeaders([
                'Api-Key' => $this->pineconeApiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'vectors' => $vectors,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("Pinecone upsert error: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Error upserting to Pinecone: " . $e->getMessage());
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
            $url = "https://{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io/query";

            $response = Http::withHeaders([
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
                return $data['matches'] ?? [];
            }

            Log::error("Pinecone query error: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Error querying Pinecone: " . $e->getMessage());
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
            $url = "https://{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io/vectors/delete";

            $response = Http::withHeaders([
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
            return [
                'document_id' => $match['metadata']['document_id'] ?? null,
                'chunk_index' => $match['metadata']['chunk_index'] ?? 0,
                'text' => $match['metadata']['text'] ?? '',
                'file_name' => $match['metadata']['file_name'] ?? '',
                'score' => $match['score'] ?? 0,
                'created_at' => $match['metadata']['created_at'] ?? null,
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
}
