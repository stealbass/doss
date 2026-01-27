<?php

namespace App\Services;

use App\Models\LegalDocument;
use App\Models\DocumentTemplate;
use App\Models\FiscalSocialResource;
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

        try {
            // Check if extracted_text column exists
            $columns = DB::getSchemaBuilder()->getColumnListing('legal_documents');
            $hasExtractedText = in_array('extracted_text', $columns);

            // Build MATCH clause dynamically
            $matchFields = 'title, description';
            if ($hasExtractedText) {
                $matchFields .= ', extracted_text';
            }

            // Perform FULLTEXT search
            $results = LegalDocument::whereRaw(
                "MATCH({$matchFields}) AGAINST(? IN NATURAL LANGUAGE MODE)",
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
                'created_at',
                DB::raw("MATCH({$matchFields}) AGAINST('{$cleanQuery}' IN NATURAL LANGUAGE MODE) as relevance_score")
            ])
            ->when($hasExtractedText, function ($query) {
                return $query->addSelect('extracted_text');
            })
            ->with('category:id,name')
            ->orderBy('relevance_score', 'DESC')
            ->limit($limit)
            ->get();

            // Format results
            return $this->formatResults($results);
        } catch (\Exception $e) {
            \Log::error('SimpleRagService search failed', ['error' => $e->getMessage(), 'query' => $query]);
            return [];
        }
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
     * Get context filtered by country
     * CRITICAL: Used by ChatController to provide country-specific legal documents
     *
     * @param string $query The search query
     * @param string $country User's country (Cameroun, Sénégal, etc.)
     * @param int $maxTokens Maximum tokens for context
     * @return string Combined context from top results
     */
    /**
     * Get RAG context with sources metadata
     * Returns both the context text and source documents info
     */
    public function getContextWithSourcesByCountry(string $query, string $country, int $maxTokens = 2000): array
    {
        $results = $this->searchByCountry($query, $country, 5);
        
        if (empty($results)) {
            return [
                'context' => '',
                'sources' => [],
            ];
        }

        $context = "=== BIBLIOTHÈQUE JURIDIQUE ({$country}) ===\n\n";
        $currentTokens = 0;
        $sources = [];

        foreach ($results as $result) {
            $docContext = $this->buildDocumentContext($result);
            $docTokens = $this->estimateTokens($docContext);

            // Stop if adding this doc would exceed token limit
            if ($currentTokens + $docTokens > $maxTokens) {
                break;
            }

            $context .= $docContext . "\n\n---\n\n";
            $currentTokens += $docTokens;
            
            // Add source metadata
            $sources[] = [
                'id' => $result['id'],
                'title' => $result['title'],
                'type' => 'legal_document',
                'file_path' => $result['file_path'] ?? null,
                'file_name' => $result['file_name'] ?? null,
                'category_id' => $result['category_id'] ?? null,
            ];
        }

        return [
            'context' => $context,
            'sources' => $sources,
        ];
    }

    public function getContextByCountry(string $query, string $country, int $maxTokens = 2000): string
    {
        $results = $this->searchByCountry($query, $country, 5);
        
        if (empty($results)) {
            return '';
        }

        $context = "=== BIBLIOTHÈQUE JURIDIQUE ({$country}) ===\n\n";
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
     * Search legal documents filtered by country
     * 
     * @param string $query The search query
     * @param string $country User's country
     * @param int $limit Number of results to return
     * @return array Search results with relevance scores
     */
    public function searchByCountry(string $query, string $country, int $limit = 5): array
    {
        // Clean and prepare query
        $cleanQuery = $this->prepareQuery($query);
        
        if (empty($cleanQuery)) {
            return [];
        }

        try {
            // Check if extracted_text column exists
            $columns = DB::getSchemaBuilder()->getColumnListing('legal_documents');
            $hasExtractedText = in_array('extracted_text', $columns);

            // Build MATCH clause dynamically
            $matchFields = 'title, description';
            if ($hasExtractedText) {
                $matchFields .= ', extracted_text';
            }

            // Trouver le code pays depuis le nom
            $countryCode = $this->getCountryCode($country);

            // Step 1: Try FULLTEXT search
            try {
                $queryBuilder = LegalDocument::whereRaw(
                    "MATCH({$matchFields}) AGAINST(? IN NATURAL LANGUAGE MODE)",
                    [$cleanQuery]
                )
                ->select([
                    'id',
                    'title',
                    'description',
                    'category_id',
                    'country',
                    'file_name',
                    'file_path',
                    'file_size',
                    'created_at',
                    DB::raw("MATCH({$matchFields}) AGAINST('{$cleanQuery}' IN NATURAL LANGUAGE MODE) as relevance_score")
                ])
                ->when($hasExtractedText, function($query) {
                    return $query->addSelect('extracted_text');
                })
                ->with('category:id,name');

                // Filter by country if provided
                if ($countryCode) {
                    // Chercher les documents du pays spécifique OU les documents OHADA (applicables à tous les pays membres)
                    $queryBuilder->where(function($q) use ($countryCode) {
                        $q->where('country', $countryCode)
                          ->orWhere('country', 'OHADA')
                          ->orWhereNull('country'); // Documents généraux
                    });
                }

                $results = $queryBuilder
                    ->orderBy('relevance_score', 'DESC')
                    ->limit($limit)
                    ->get();
                    
                if (!$results->isEmpty()) {
                    return $this->formatResults($results);
                }
            } catch (\Exception $e) {
                \Log::warning('FULLTEXT search failed, trying LIKE fallback', ['error' => $e->getMessage()]);
            }

            // Step 2: Fallback to LIKE search if FULLTEXT fails
            \Log::info('Using LIKE fallback search for legal documents', ['query' => $cleanQuery, 'country' => $countryCode]);
            
            $queryBuilder = LegalDocument::where(function($q) use ($cleanQuery) {
                $q->where('title', 'LIKE', "%{$cleanQuery}%")
                  ->orWhere('description', 'LIKE', "%{$cleanQuery}%");
            })
            ->with('category:id,name');

            if ($countryCode) {
                $queryBuilder->where(function($q) use ($countryCode) {
                    $q->where('country', $countryCode)
                      ->orWhere('country', 'OHADA')
                      ->orWhereNull('country');
                });
            }

            $results = $queryBuilder
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();

            return $this->formatResults($results);
            
        } catch (\Exception $e) {
            \Log::error('SimpleRagService searchByCountry failed', ['error' => $e->getMessage(), 'query' => $query, 'country' => $country]);
            return [];
        }
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
        
        // Ajouter le pays si disponible
        if (!empty($document['country'])) {
            $countryName = $this->getCountryName($document['country']);
            $context .= "Pays/Juridiction: {$countryName}\n";
        }
        
        if (!empty($document['description'])) {
            $context .= "Description: {$document['description']}\n";
        }

        if (!empty($document['excerpt'])) {
            $context .= "Extrait: {$document['excerpt']}\n";
        }

        return $context;
    }

    /**
     * Get country code from country name
     * Supports French/English variants (Cameroun/Cameroon, etc.)
     * 
     * @param string $countryName Country name (Cameroun, Sénégal, etc.)
     * @return string|null Country code (CM, SN, etc.) or null
     */
    private function getCountryCode(string $countryName): ?string
    {
        // Manual mapping for common variants
        $manualMapping = [
            'Cameroun' => 'CM',
            'Cameroon' => 'CM',
            'Sénégal' => 'SN',
            'Senegal' => 'SN',
            'Côte d\'Ivoire' => 'CI',
            'Ivory Coast' => 'CI',
            'Gabon' => 'GA',
            'Mali' => 'ML',
            'Niger' => 'NE',
            'Burkina Faso' => 'BF',
            'Tchad' => 'TD',
            'Chad' => 'TD',
            'Bénin' => 'BJ',
            'Benin' => 'BJ',
            'Togo' => 'TG',
        ];
        
        // Try manual mapping first
        if (isset($manualMapping[$countryName])) {
            \Log::info('Country code found via manual mapping', [
                'input' => $countryName,
                'code' => $manualMapping[$countryName],
            ]);
            return $manualMapping[$countryName];
        }
        
        // Fallback to config
        $countries = config('mobile_countries.supported_countries', []);
        
        foreach ($countries as $code => $data) {
            if ($data['name'] === $countryName || $code === $countryName) {
                return $code;
            }
        }
        
        \Log::warning('Country code not found', ['input' => $countryName]);
        return null;
    }

    /**
     * Get country name from country code
     * 
     * @param string $countryCode Country code (CM, SN, OHADA, etc.)
     * @return string Country name
     */
    private function getCountryName(string $countryCode): string
    {
        if ($countryCode === 'OHADA') {
            return 'OHADA (Tous pays membres)';
        }
        
        $countries = config('mobile_countries.supported_countries', []);
        return $countries[$countryCode]['name'] ?? $countryCode;
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
                'country' => $doc->country ?? null,
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
        
        $totalTemplates = DocumentTemplate::count();
        $totalFiscalResources = FiscalSocialResource::count();

        return [
            'legal_documents' => [
                'total' => $totalDocs,
                'indexed' => $docsWithText,
                'coverage' => $totalDocs > 0 ? round(($docsWithText / $totalDocs) * 100, 2) : 0,
            ],
            'document_templates' => [
                'total' => $totalTemplates,
            ],
            'fiscal_social_resources' => [
                'total' => $totalFiscalResources,
            ],
        ];
    }

    /**
     * Search document templates by country
     * 
     * @param string $query Search query
     * @param string $country User's country
     * @param int $limit Number of results
     * @return array Template results
     */
    public function searchTemplatesByCountry(string $query, string $country, int $limit = 3): array
    {
        $cleanQuery = $this->prepareQuery($query);
        
        if (empty($cleanQuery)) {
            return [];
        }

        $countryCode = $this->getCountryCode($country);

        // Step 1: Try with mobile visibility filter
        $queryBuilder = DocumentTemplate::where('is_mobile_visible', true)
            ->where(function($q) use ($cleanQuery) {
                $q->where('name', 'LIKE', "%{$cleanQuery}%")
                  ->orWhere('description', 'LIKE', "%{$cleanQuery}%")
                  ->orWhere('ai_context', 'LIKE', "%{$cleanQuery}%");
            })
            ->with('category:id,name');

        if ($countryCode) {
            $queryBuilder->where(function($q) use ($countryCode) {
                $q->where('country', $countryCode)
                  ->orWhereNull('country'); // Templates généraux
            });
        }

        $results = $queryBuilder->limit($limit)->get();

        // Step 2: Fallback if no results - try without mobile filter
        if ($results->isEmpty() && !empty($cleanQuery)) {
            \Log::warning('Template search fallback: no visible results, trying all templates', [
                'query' => $cleanQuery,
                'country' => $countryCode,
            ]);
            
            $queryBuilder = DocumentTemplate::where(function($q) use ($cleanQuery) {
                $q->where('name', 'LIKE', "%{$cleanQuery}%")
                  ->orWhere('description', 'LIKE', "%{$cleanQuery}%")
                  ->orWhere('ai_context', 'LIKE', "%{$cleanQuery}%");
            })
            ->with('category:id,name');

            if ($countryCode) {
                $queryBuilder->where(function($q) use ($countryCode) {
                    $q->where('country', $countryCode)
                      ->orWhereNull('country');
                });
            }

            $results = $queryBuilder->limit($limit)->get();
        }

        // Step 3: Final fallback - return all templates for country
        if ($results->isEmpty() && !empty($countryCode)) {
            \Log::warning('Template search final fallback: returning all templates for country', [
                'country' => $countryCode,
            ]);
            
            $results = DocumentTemplate::where(function($q) use ($countryCode) {
                $q->where('country', $countryCode)
                  ->orWhereNull('country');
            })
            ->with('category:id,name')
            ->limit($limit)
            ->get();
        }

        return $results->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category->name ?? 'N/A',
                'country' => $template->country ?? null,
                'template_type' => $template->template_type,
                'ai_context' => $template->ai_context,
            ];
        })->toArray();
    }

    /**
     * Search fiscal/social resources by country and year
     * With fallback for restrictive filters
     * 
     * @param string $query Search query
     * @param string $country User's country
     * @param int $year Current year (defaults to current year)
     * @param int $limit Number of results
     * @return array Fiscal resources results
     */
    public function searchFiscalResourcesByCountry(string $query, string $country, ?int $year = null, int $limit = 3): array
    {
        $cleanQuery = $this->prepareQuery($query);
        $countryCode = $this->getCountryCode($country);
        $year = $year ?? date('Y');

        \Log::info('🔍 Fiscal search initiated', [
            'raw_query' => $query,
            'clean_query' => $cleanQuery,
            'raw_country' => $country,
            'country_code' => $countryCode,
            'year' => $year,
        ]);

        // Step 1: Try strict search with filters
        $queryBuilder = FiscalSocialResource::where('is_mobile_visible', true)
            ->where('is_latest_version', true)
            ->where(function($q) use ($cleanQuery) {
                if (!empty($cleanQuery)) {
                    $q->where('title', 'LIKE', "%{$cleanQuery}%")
                      ->orWhere('description', 'LIKE', "%{$cleanQuery}%")
                      ->orWhere('ai_context', 'LIKE', "%{$cleanQuery}%");
                }
            })
            ->with('category:id,name');

        if ($countryCode) {
            $queryBuilder->where('country', $countryCode);
        }

        // Prioritize current year resources
        $queryBuilder->orderByRaw("CASE WHEN year = {$year} THEN 0 ELSE 1 END")
                     ->orderBy('year', 'DESC');

        $results = $queryBuilder->limit($limit)->get();
        \Log::info('📊 Fiscal Level 1 (strict filters)', ['results' => $results->count()]);

        // Step 2: Fallback if no results - try without mobile visibility filter
        if ($results->isEmpty() && !empty($cleanQuery)) {
            \Log::warning('⚠️ Fiscal fallback Level 2: trying without strict filters', [
                'query' => $cleanQuery,
                'country' => $countryCode,
            ]);
            
            $queryBuilder = FiscalSocialResource::where(function($q) use ($cleanQuery) {
                $q->where('title', 'LIKE', "%{$cleanQuery}%")
                  ->orWhere('description', 'LIKE', "%{$cleanQuery}%")
                  ->orWhere('ai_context', 'LIKE', "%{$cleanQuery}%");
            })
            ->with('category:id,name');

            if ($countryCode) {
                $queryBuilder->where('country', $countryCode);
            }

            $results = $queryBuilder->limit($limit)->get();
            \Log::info('📊 Fiscal Level 2 (no filters)', ['results' => $results->count()]);
        }

        // Step 3: Final fallback - if still empty, return ALL resources for country
        if ($results->isEmpty() && !empty($countryCode)) {
            \Log::warning('⚠️ Fiscal fallback Level 3: all resources for country', [
                'country' => $countryCode,
            ]);
            
            $results = FiscalSocialResource::where('country', $countryCode)
                ->with('category:id,name')
                ->limit($limit)
                ->get();
            \Log::info('📊 Fiscal Level 3 (all country)', ['results' => $results->count()]);
        }
        
        // Step 4: ULTIMATE fallback - return ANY fiscal resources
        if ($results->isEmpty()) {
            \Log::error('🚨 Fiscal CRITICAL: No resources found AT ALL, returning any available');
            
            $results = FiscalSocialResource::with('category:id,name')
                ->limit($limit)
                ->get();
            \Log::info('📊 Fiscal Level 4 (any)', ['results' => $results->count()]);
        }

        $mapped = $results->map(function ($resource) {
            return [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'content' => $resource->content ?? $resource->description,
                'category' => $resource->category->name ?? 'N/A',
                'country' => $resource->country ?? null,
                'year' => $resource->year,
                'resource_type' => $resource->resource_type,
                'ai_context' => $resource->ai_context,
                'key_points' => $resource->key_points,
            ];
        })->toArray();
        
        \Log::info('✅ Fiscal search completed', [
            'final_count' => count($mapped),
            'titles' => array_column($mapped, 'title'),
        ]);

        return $mapped;
    }

    /**
     * Get comprehensive context with sources including all sources (Legal Docs + Templates + Fiscal)
     * Returns both context and source metadata for clickable citations
     * 
     * @param string $query Search query
     * @param string $country User's country
     * @param int $maxTokens Maximum tokens
     * @return array ['context' => string, 'sources' => array]
     */
    public function getContextWithMultipleSourcesByCountry(string $query, string $country, int $maxTokens = 2000): array
    {
        $context = "";
        $currentTokens = 0;
        $allSources = [];

        // 1. Legal Documents (Bibliothèque Juridique) - 50% of budget
        $legalLimit = (int)($maxTokens * 0.5);
        $legalResults = $this->searchByCountry($query, $country, 5);
        if (!empty($legalResults)) {
            $legalContext = "=== BIBLIOTHÈQUE JURIDIQUE ({$country}) ===\n\n";
            $legalTokens = 0;
            foreach ($legalResults as $doc) {
                $docContext = $this->buildDocumentContext($doc);
                $tokens = $this->estimateTokens($docContext);
                
                if ($legalTokens + $tokens > $legalLimit) break;
                
                $legalContext .= $docContext . "\n\n---\n\n";
                $legalTokens += $tokens;
                $currentTokens += $tokens;
                
                // Add source
                $allSources[] = [
                    'id' => $doc['id'],
                    'title' => $doc['title'],
                    'type' => 'legal_document',
                    'file_path' => $doc['file_path'] ?? null,
                    'file_name' => $doc['file_name'] ?? null,
                    'category_id' => $doc['category_id'] ?? null,
                ];
            }
            $context .= $legalContext;
        }

        // 2. Document Templates - 25% of budget
        if ($currentTokens < $maxTokens * 0.75) {
            $templateLimit = (int)($maxTokens * 0.25);
            $templateResults = $this->searchTemplatesByCountry($query, $country, 5);
            if (!empty($templateResults)) {
                $templateContext = "=== MODÈLES DE DOCUMENTS ({$country}) ===\n\n";
                $templateTokens = 0;
                foreach ($templateResults as $template) {
                    $tplContext = "Modèle: {$template['name']}\n";
                    $tplContext .= "Type: {$template['template_type']}\n";
                    $tplContext .= "Catégorie: {$template['category']}\n";
                    if (!empty($template['ai_context'])) {
                        $tplContext .= "Contexte: {$template['ai_context']}\n";
                    }
                    
                    $tokens = $this->estimateTokens($tplContext);
                    if ($templateTokens + $tokens > $templateLimit) break;
                    
                    $templateContext .= $tplContext . "\n\n---\n\n";
                    $templateTokens += $tokens;
                    $currentTokens += $tokens;
                    
                    // Add source
                    $allSources[] = [
                        'id' => $template['id'],
                        'title' => $template['name'],
                        'type' => 'document_template',
                        'file_path' => null,
                        'file_name' => null,
                        'category_id' => $template['category'],
                    ];
                }
                $context .= $templateContext;
            }
        }

        // 3. Fiscal/Social Resources - 25% of budget
        if ($currentTokens < $maxTokens * 0.9) {
            $fiscalLimit = (int)($maxTokens * 0.25);
            $fiscalResults = $this->searchFiscalResourcesByCountry($query, $country, null, 5);
            if (!empty($fiscalResults)) {
                $fiscalContext = "=== RESSOURCES FISCALES & SOCIALES ({$country}) ===\n\n";
                $fiscalTokens = 0;
                foreach ($fiscalResults as $resource) {
                    $resContext = "Ressource: {$resource['title']}\n";
                    $resContext .= "Année: {$resource['year']}\n";
                    $resContext .= "Type: {$resource['resource_type']}\n";
                    $resContext .= "Catégorie: {$resource['category']}\n";
                    
                    // Add description
                    if (!empty($resource['description'])) {
                        $resContext .= "Description: {$resource['description']}\n";
                    }
                    
                    // Add AI context
                    if (!empty($resource['ai_context'])) {
                        $resContext .= "Contexte: {$resource['ai_context']}\n";
                    }
                    
                    // Add key points
                    if (!empty($resource['key_points'])) {
                        $keyPointsStr = is_array($resource['key_points']) 
                            ? implode(', ', $resource['key_points'])
                            : $resource['key_points'];
                        $resContext .= "Points clés: {$keyPointsStr}\n";
                    }
                    
                    // Add content for better context
                    if (!empty($resource['content'])) {
                        $contentExcerpt = substr($resource['content'], 0, 300);
                        if (strlen($resource['content']) > 300) {
                            $contentExcerpt .= '...';
                        }
                        $resContext .= "Contenu: {$contentExcerpt}\n";
                    }
                    
                    $tokens = $this->estimateTokens($resContext);
                    if ($fiscalTokens + $tokens > $fiscalLimit) break;
                    
                    $fiscalContext .= $resContext . "\n\n---\n\n";
                    $fiscalTokens += $tokens;
                    $currentTokens += $tokens;
                    
                    // Add source
                    $allSources[] = [
                        'id' => $resource['id'],
                        'title' => $resource['title'],
                        'type' => 'fiscal_resource',
                        'file_path' => null,
                        'file_name' => null,
                        'category_id' => null,
                    ];
                }
                $context .= $fiscalContext;
            }
        }

        return [
            'context' => $context,
            'sources' => $allSources,
        ];
    }

    /**
     * Get comprehensive context including all sources (Legal Docs + Templates + Fiscal)
     * 
     * @param string $query Search query
     * @param string $country User's country
     * @param int $maxTokens Maximum tokens
     * @return string Combined context from all sources
     */
    public function getComprehensiveContextByCountry(string $query, string $country, int $maxTokens = 2000): string
    {
        $context = "";
        $currentTokens = 0;

        // 1. Legal Documents (Bibliothèque Juridique)
        $legalResults = $this->searchByCountry($query, $country, 3);
        if (!empty($legalResults)) {
            $legalContext = "=== BIBLIOTHÈQUE JURIDIQUE ({$country}) ===\n\n";
            foreach ($legalResults as $doc) {
                $docContext = $this->buildDocumentContext($doc);
                $tokens = $this->estimateTokens($docContext);
                
                if ($currentTokens + $tokens > $maxTokens) break;
                
                $legalContext .= $docContext . "\n\n---\n\n";
                $currentTokens += $tokens;
            }
            $context .= $legalContext;
        }

        // 2. Document Templates
        if ($currentTokens < $maxTokens * 0.8) { // Garder 20% pour les templates et fiscalité
            $templateResults = $this->searchTemplatesByCountry($query, $country, 2);
            if (!empty($templateResults)) {
                $templateContext = "=== MODÈLES DE DOCUMENTS ({$country}) ===\n\n";
                foreach ($templateResults as $template) {
                    $tplContext = "Modèle: {$template['name']}\n";
                    $tplContext .= "Catégorie: {$template['category']}\n";
                    if (!empty($template['ai_context'])) {
                        $tplContext .= "Contexte: {$template['ai_context']}\n";
                    }
                    
                    $tokens = $this->estimateTokens($tplContext);
                    if ($currentTokens + $tokens > $maxTokens) break;
                    
                    $templateContext .= $tplContext . "\n\n---\n\n";
                    $currentTokens += $tokens;
                }
                $context .= $templateContext;
            }
        }

        // 3. Fiscal/Social Resources
        if ($currentTokens < $maxTokens * 0.9) {
            $fiscalResults = $this->searchFiscalResourcesByCountry($query, $country, null, 2);
            if (!empty($fiscalResults)) {
                $fiscalContext = "=== RESSOURCES FISCALES & SOCIALES ({$country}) ===\n\n";
                foreach ($fiscalResults as $resource) {
                    $resContext = "Ressource: {$resource['title']}\n";
                    $resContext .= "Année: {$resource['year']}\n";
                    $resContext .= "Type: {$resource['resource_type']}\n";
                    if (!empty($resource['ai_context'])) {
                        $resContext .= "Contexte: {$resource['ai_context']}\n";
                    }
                    if (!empty($resource['key_points'])) {
                        $resContext .= "Points clés: " . implode(', ', $resource['key_points']) . "\n";
                    }
                    
                    $tokens = $this->estimateTokens($resContext);
                    if ($currentTokens + $tokens > $maxTokens) break;
                    
                    $fiscalContext .= $resContext . "\n\n---\n\n";
                    $currentTokens += $tokens;
                }
                $context .= $fiscalContext;
            }
        }

        return $context;
    }

    /**
     * Smart RAG context with priority based on question type
     * Prioritizes the most relevant source type for the question
     * 
     * @param string $query The search query
     * @param string $country The user's country
     * @param string $priority Question type: 'fiscal', 'template', 'legal', or 'mixed'
     * @param int $maxTokens Maximum tokens
     * @return array ['context' => string, 'sources' => array]
     */
    public function getContextByPriority(
        string $query,
        string $country,
        string $priority = 'legal',
        int $maxTokens = 2000
    ): array {
        $context = "";
        $currentTokens = 0;
        $allSources = [];

        if ($priority === 'fiscal') {
            // FISCAL PRIORITY: fiscal (50%) → templates (30%) → legal (20%)
            
            // 1. Fiscal Resources - 50%
            $fiscalLimit = (int)($maxTokens * 0.5);
            $fiscalResults = $this->searchFiscalResourcesByCountry($query, $country, null, 8);
            if (!empty($fiscalResults)) {
                $fiscalContext = "=== RESSOURCES FISCALES & SOCIALES ({$country}) ===\n\n";
                $fiscalTokens = 0;
                foreach ($fiscalResults as $resource) {
                    $resContext = "Ressource: {$resource['title']}\n";
                    $resContext .= "Année: {$resource['year']}\n";
                    $resContext .= "Type: {$resource['resource_type']}\n";
                    if (!empty($resource['description'])) {
                        $resContext .= "Description: {$resource['description']}\n";
                    }
                    if (!empty($resource['ai_context'])) {
                        $resContext .= "Contexte: {$resource['ai_context']}\n";
                    }
                    if (!empty($resource['key_points'])) {
                        $keyPointsStr = is_array($resource['key_points']) 
                            ? implode(', ', $resource['key_points'])
                            : $resource['key_points'];
                        $resContext .= "Points clés: {$keyPointsStr}\n";
                    }
                    if (!empty($resource['content'])) {
                        $contentExcerpt = substr($resource['content'], 0, 500);
                        if (strlen($resource['content']) > 500) {
                            $contentExcerpt .= '...';
                        }
                        $resContext .= "Contenu: {$contentExcerpt}\n";
                    }
                    
                    $tokens = $this->estimateTokens($resContext);
                    if ($fiscalTokens + $tokens > $fiscalLimit) break;
                    
                    $fiscalContext .= $resContext . "\n\n---\n\n";
                    $fiscalTokens += $tokens;
                    $currentTokens += $tokens;
                    
                    $allSources[] = [
                        'id' => $resource['id'],
                        'title' => $resource['title'],
                        'type' => 'fiscal_resource',
                    ];
                }
                $context .= $fiscalContext;
            }
            
            // 2. Templates - 30% (if still have budget)
            if ($currentTokens < $maxTokens * 0.8) {
                $templateLimit = (int)($maxTokens * 0.3);
                $templateResults = $this->searchTemplatesByCountry($query, $country, 5);
                if (!empty($templateResults)) {
                    $templateContext = "=== MODÈLES DE DOCUMENTS ({$country}) ===\n\n";
                    $templateTokens = 0;
                    foreach ($templateResults as $template) {
                        $tplContext = "Modèle: {$template['name']}\n";
                        $tplContext .= "Type: {$template['template_type']}\n";
                        if (!empty($template['ai_context'])) {
                            $tplContext .= "Contexte: {$template['ai_context']}\n";
                        }
                        
                        $tokens = $this->estimateTokens($tplContext);
                        if ($templateTokens + $tokens > $templateLimit) break;
                        
                        $templateContext .= $tplContext . "\n\n---\n\n";
                        $templateTokens += $tokens;
                        $currentTokens += $tokens;
                        
                        $allSources[] = [
                            'id' => $template['id'],
                            'title' => $template['name'],
                            'type' => 'document_template',
                        ];
                    }
                    $context .= $templateContext;
                }
            }
            
            // 3. Legal - 20% (if still have budget)
            if ($currentTokens < $maxTokens * 0.9) {
                $legalLimit = (int)($maxTokens * 0.2);
                $legalResults = $this->searchByCountry($query, $country, 5);
                if (!empty($legalResults)) {
                    $legalContext = "=== BIBLIOTHÈQUE JURIDIQUE ({$country}) ===\n\n";
                    $legalTokens = 0;
                    foreach ($legalResults as $doc) {
                        $docContext = $this->buildDocumentContext($doc);
                        $tokens = $this->estimateTokens($docContext);
                        if ($legalTokens + $tokens > $legalLimit) break;
                        
                        $legalContext .= $docContext . "\n\n---\n\n";
                        $legalTokens += $tokens;
                        $currentTokens += $tokens;
                        
                        $allSources[] = [
                            'id' => $doc['id'],
                            'title' => $doc['title'],
                            'type' => 'legal_document',
                        ];
                    }
                    $context .= $legalContext;
                }
            }
            
        } elseif ($priority === 'template') {
            // TEMPLATE PRIORITY: templates (50%) → legal (35%) → fiscal (15%)
            
            // 1. Templates - 50%
            $templateLimit = (int)($maxTokens * 0.5);
            $templateResults = $this->searchTemplatesByCountry($query, $country, 8);
            if (!empty($templateResults)) {
                $templateContext = "=== MODÈLES DE DOCUMENTS ({$country}) ===\n\n";
                $templateTokens = 0;
                foreach ($templateResults as $template) {
                    $tplContext = "Modèle: {$template['name']}\n";
                    $tplContext .= "Type: {$template['template_type']}\n";
                    $tplContext .= "Catégorie: {$template['category']}\n";
                    if (!empty($template['ai_context'])) {
                        $tplContext .= "Contexte: {$template['ai_context']}\n";
                    }
                    
                    $tokens = $this->estimateTokens($tplContext);
                    if ($templateTokens + $tokens > $templateLimit) break;
                    
                    $templateContext .= $tplContext . "\n\n---\n\n";
                    $templateTokens += $tokens;
                    $currentTokens += $tokens;
                    
                    $allSources[] = [
                        'id' => $template['id'],
                        'title' => $template['name'],
                        'type' => 'document_template',
                    ];
                }
                $context .= $templateContext;
            }
            
            // 2. Legal - 35%
            if ($currentTokens < $maxTokens * 0.85) {
                $legalLimit = (int)($maxTokens * 0.35);
                $legalResults = $this->searchByCountry($query, $country, 5);
                if (!empty($legalResults)) {
                    $legalContext = "=== BIBLIOTHÈQUE JURIDIQUE ({$country}) ===\n\n";
                    $legalTokens = 0;
                    foreach ($legalResults as $doc) {
                        $docContext = $this->buildDocumentContext($doc);
                        $tokens = $this->estimateTokens($docContext);
                        if ($legalTokens + $tokens > $legalLimit) break;
                        
                        $legalContext .= $docContext . "\n\n---\n\n";
                        $legalTokens += $tokens;
                        $currentTokens += $tokens;
                        
                        $allSources[] = [
                            'id' => $doc['id'],
                            'title' => $doc['title'],
                            'type' => 'legal_document',
                        ];
                    }
                    $context .= $legalContext;
                }
            }
            
            // 3. Fiscal - 15%
            if ($currentTokens < $maxTokens * 0.9) {
                $fiscalLimit = (int)($maxTokens * 0.15);
                $fiscalResults = $this->searchFiscalResourcesByCountry($query, $country, null, 5);
                if (!empty($fiscalResults)) {
                    $fiscalContext = "=== RESSOURCES FISCALES & SOCIALES ({$country}) ===\n\n";
                    $fiscalTokens = 0;
                    foreach ($fiscalResults as $resource) {
                        $resContext = "Ressource: {$resource['title']}\n";
                        $resContext .= "Type: {$resource['resource_type']}\n";
                        if (!empty($resource['ai_context'])) {
                            $resContext .= "Contexte: {$resource['ai_context']}\n";
                        }
                        
                        $tokens = $this->estimateTokens($resContext);
                        if ($fiscalTokens + $tokens > $fiscalLimit) break;
                        
                        $fiscalContext .= $resContext . "\n\n---\n\n";
                        $fiscalTokens += $tokens;
                        $currentTokens += $tokens;
                        
                        $allSources[] = [
                            'id' => $resource['id'],
                            'title' => $resource['title'],
                            'type' => 'fiscal_resource',
                        ];
                    }
                    $context .= $fiscalContext;
                }
            }
            
        } else {
            // LEGAL PRIORITY (default): ONLY legal sources (50% legal)
            $legalLimit = (int)($maxTokens * 0.5);
            $legalResults = $this->searchByCountry($query, $country, 5);
            if (!empty($legalResults)) {
                $legalContext = "=== BIBLIOTHÈQUE JURIDIQUE ({$country}) ===\n\n";
                $legalTokens = 0;
                foreach ($legalResults as $doc) {
                    $docContext = $this->buildDocumentContext($doc);
                    $tokens = $this->estimateTokens($docContext);
                    
                    if ($legalTokens + $tokens > $legalLimit) break;
                    
                    $legalContext .= $docContext . "\n\n---\n\n";
                    $legalTokens += $tokens;
                    $currentTokens += $tokens;
                    
                    $allSources[] = [
                        'id' => $doc['id'],
                        'title' => $doc['title'],
                        'type' => 'legal_document',
                        'file_path' => $doc['file_path'] ?? null,
                        'file_name' => $doc['file_name'] ?? null,
                        'category_id' => $doc['category_id'] ?? null,
                    ];
                }
                $context .= $legalContext;
            }
        }

        \Log::info('RAG: Smart context by priority', [
            'query' => $query,
            'priority' => $priority,
            'sources_found' => count($allSources),
            'context_length' => strlen($context),
        ]);

        return [
            'context' => $context,
            'sources' => $allSources,
        ];
    }
}
