<?php

namespace App\Services;

use App\Models\LegalDocument;
use Illuminate\Support\Facades\DB;

/**
 * Simple RAG Service
 * 
 * Uses MySQL FULLTEXT search on legal_documents table
 * For searching in the global legal library (jurisprudence, laws, etc.)
 */
class SimpleRagService
{
    /**
     * Search legal documents using FULLTEXT search
     * 
     * @param string $query The search query
     * @param int $limit Number of results to return
     * @return array Search results with relevance scores
     */
    public function search(string $query, int $limit = 5): array
    {
        // Clean and prepare query
        $cleanQuery = $this->prepareQuery($query);
        
        if (empty($cleanQuery)) {
            return [];
        }

        // Perform FULLTEXT search
        $results = LegalDocument::whereRaw(
            "MATCH(title, description, extracted_text) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$cleanQuery]
        )
        ->select([
            'id',
            'title',
            'description',
            'category_id',
            'file_name',
            'file_path',
            'file_size',
            'extracted_text',
            'created_at',
            DB::raw("MATCH(title, description, extracted_text) AGAINST('{$cleanQuery}' IN NATURAL LANGUAGE MODE) as relevance_score")
        ])
        ->with('category:id,name')
        ->orderBy('relevance_score', 'DESC')
        ->limit($limit)
        ->get();

        // Format results
        return $this->formatResults($results);
    }

    /**
     * Get context from top search results
     * 
     * @param string $query The search query
     * @param int $maxTokens Maximum tokens for context
     * @return string Combined context from top results
     */
    public function getContext(string $query, int $maxTokens = 2000): string
    {
        $results = $this->search($query, 5);
        
        if (empty($results)) {
            return '';
        }

        $context = "Documents juridiques pertinents:\n\n";
        $currentTokens = 0;

        foreach ($results as $result) {
            $docContext = $this->buildDocumentContext($result);
            $docTokens = $this->estimateTokens($docContext);

            // Stop if adding this doc would exceed token limit
            if ($currentTokens + $docTokens > $maxTokens) {
                break;
            }

            $context .= $docContext . "\n\n---\n\n";
            $currentTokens += $docTokens;
        }

        return $context;
    }

    /**
     * Build context string for a single document
     * 
     * @param array $document Document data
     * @return string Formatted context
     */
    private function buildDocumentContext(array $document): string
    {
        $context = "Document: {$document['title']}\n";
        $context .= "Catégorie: {$document['category_name']}\n";
        
        if (!empty($document['description'])) {
            $context .= "Description: {$document['description']}\n";
        }

        if (!empty($document['excerpt'])) {
            $context .= "Extrait: {$document['excerpt']}\n";
        }

        return $context;
    }

    /**
     * Prepare query for FULLTEXT search
     * 
     * @param string $query Raw query
     * @return string Cleaned query
     */
    private function prepareQuery(string $query): string
    {
        // Remove special characters that might break FULLTEXT
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        
        // Remove extra spaces
        $query = preg_replace('/\s+/', ' ', $query);
        
        return trim($query);
    }

    /**
     * Format search results
     * 
     * @param \Illuminate\Support\Collection $results
     * @return array Formatted results
     */
    private function formatResults($results): array
    {
        return $results->map(function ($doc) {
            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'description' => $doc->description,
                'category_name' => $doc->category->name ?? 'N/A',
                'file_name' => $doc->file_name,
                'file_path' => $doc->file_path,
                'relevance_score' => $doc->relevance_score,
                'excerpt' => $this->extractExcerpt($doc->extracted_text ?? '', 200),
                'created_at' => $doc->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Extract excerpt from text
     * 
     * @param string $text Full text
     * @param int $maxLength Maximum excerpt length
     * @return string Excerpt
     */
    private function extractExcerpt(string $text, int $maxLength = 200): string
    {
        if (empty($text)) {
            return '';
        }

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength) . '...';
    }

    /**
     * Estimate token count (rough approximation)
     * 1 token ≈ 4 characters for French text
     * 
     * @param string $text
     * @return int Estimated tokens
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get statistics about the search index
     * 
     * @return array Index statistics
     */
    public function getIndexStats(): array
    {
        $totalDocs = LegalDocument::count();
        $docsWithText = LegalDocument::whereNotNull('extracted_text')
            ->where('extracted_text', '!=', '')
            ->count();

        return [
            'total_documents' => $totalDocs,
            'indexed_documents' => $docsWithText,
            'index_coverage' => $totalDocs > 0 ? round(($docsWithText / $totalDocs) * 100, 2) : 0,
        ];
    }
}
