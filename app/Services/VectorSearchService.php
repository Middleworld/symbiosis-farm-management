<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VectorSearchService
{
    protected EmbeddingService $embeddingService;
    
    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }
    
    /**
     * Perform semantic search across all knowledge bases
     *
     * @param string $query The user's natural language query
     * @param int $limit Maximum number of results to return
     * @param array $filters Optional filters (e.g., ['crop_family' => 'Brassica'])
     * @return array Array of relevant knowledge entries with similarity scores
     */
    public function semanticSearch(string $query, int $limit = 10, array $filters = []): array
    {
        // Generate embedding for the query
        $queryEmbedding = $this->embeddingService->embed($query);
        
        // If embedding service is unavailable, return empty results
        if ($queryEmbedding === null || !is_array($queryEmbedding)) {
            Log::debug('Embedding service unavailable for semantic search');
            return [];
        }
        
        // Search across all three knowledge bases
        $companionResults = $this->searchTable(
            'companion_planting_knowledge',
            $queryEmbedding,
            $limit,
            $filters
        );
        
        $rotationResults = $this->searchTable(
            'crop_rotation_knowledge',
            $queryEmbedding,
            $limit,
            $filters
        );
        
        $calendarResults = $this->searchTable(
            'uk_planting_calendar',
            $queryEmbedding,
            $limit,
            $filters
        );
        
        $generalResults = $this->searchTable(
            'general_knowledge',
            $queryEmbedding,
            $limit,
            $filters
        );
        
        // Combine and sort by similarity
        $allResults = array_merge($companionResults, $rotationResults, $calendarResults, $generalResults);
        
        usort($allResults, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($allResults, 0, $limit);
    }
    
    /**
     * Search within a specific knowledge type
     *
     * @param string $knowledgeType 'companion', 'rotation', or 'calendar'
     * @param string $query The user's natural language query
     * @param int $limit Maximum number of results to return
     * @param array $filters Optional filters
     * @return array Array of relevant knowledge entries with similarity scores
     */
    public function searchKnowledgeType(string $knowledgeType, string $query, int $limit = 5, array $filters = []): array
    {
        $tableMap = [
            'companion' => 'companion_planting_knowledge',
            'rotation' => 'crop_rotation_knowledge',
            'calendar' => 'uk_planting_calendar',
        ];
        
        if (!isset($tableMap[$knowledgeType])) {
            throw new \InvalidArgumentException("Unknown knowledge type: {$knowledgeType}");
        }
        
        $queryEmbedding = $this->embeddingService->embed($query);
        
        // If embedding service is unavailable, return empty results
        if ($queryEmbedding === null || !is_array($queryEmbedding)) {
            Log::debug('Embedding service unavailable for knowledge search');
            return [];
        }
        
        return $this->searchTable($tableMap[$knowledgeType], $queryEmbedding, $limit, $filters);
    }
    
    /**
     * Search a specific table using vector similarity
     *
     * @param string $tableName PostgreSQL table name
     * @param array $queryEmbedding Vector embedding of the query
     * @param int $limit Maximum results
     * @param array $filters Column filters
     * @return array Results with similarity scores
     */
    protected function searchTable(string $tableName, array $queryEmbedding, int $limit, array $filters = []): array
    {
        $pgsql = DB::connection('pgsql_rag');
        
        // Convert embedding to PostgreSQL vector format
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';
        
        // Build query
        $query = $pgsql->table($tableName)
            ->selectRaw("*, 1 - (embedding <=> '{$vectorString}'::vector) as similarity")
            ->whereNotNull('embedding');
        
        // Apply filters
        foreach ($filters as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }
        
        // Order by similarity (cosine distance)
        try {
            $results = $query
                ->orderByRaw("embedding <=> '{$vectorString}'::vector")
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            // If there's a dimension mismatch, return empty array for this table
            if (str_contains($e->getMessage(), 'different vector dimensions')) {
                return [];
            }
            throw $e; // Re-throw other exceptions
        }
        
        // Add knowledge type to each result
        $knowledgeType = $this->getKnowledgeType($tableName);
        
        return $results->map(function($result) use ($knowledgeType) {
            $data = (array) $result;
            $data['knowledge_type'] = $knowledgeType;
            return $data;
        })->toArray();
    }
    
    /**
     * Get knowledge type from table name
     */
    protected function getKnowledgeType(string $tableName): string
    {
        return match($tableName) {
            'companion_planting_knowledge' => 'companion',
            'crop_rotation_knowledge' => 'rotation',
            'uk_planting_calendar' => 'calendar',
            'general_knowledge' => 'general',
            default => 'unknown',
        };
    }
    
    /**
     * Format search results for AI context injection
     *
     * @param array $results Search results from semanticSearch()
     * @return string Formatted text for AI prompt
     */
    public function formatResultsForAI(array $results): string
    {
        if (empty($results)) {
            return "No relevant knowledge found.";
        }
        
        $formatted = "=== RELEVANT KNOWLEDGE (from vector database) ===\n\n";
        
        foreach ($results as $index => $result) {
            $num = $index + 1;
            $similarity = round($result['similarity'] * 100, 1);
            $type = strtoupper($result['knowledge_type']);
            
            $formatted .= "[{$num}] {$type} (Relevance: {$similarity}%)\n";
            $formatted .= $this->formatResultByType($result);
            $formatted .= "\n";
        }
        
        return $formatted;
    }
    
    /**
     * Format individual result based on knowledge type
     */
    protected function formatResultByType(array $result): string
    {
        return match($result['knowledge_type']) {
            'companion' => $this->formatCompanionResult($result),
            'rotation' => $this->formatRotationResult($result),
            'calendar' => $this->formatCalendarResult($result),
            default => json_encode($result),
        };
    }
    
    protected function formatCompanionResult(array $r): string
    {
        $output = "Primary Crop: {$r['primary_crop']} ({$r['primary_crop_family']})\n";
        $output .= "Companion: {$r['companion_plant']} ({$r['companion_family']})\n";
        $output .= "Relationship: {$r['relationship_type']}\n";
        if (!empty($r['benefits'])) $output .= "Benefits: {$r['benefits']}\n";
        if (!empty($r['planting_notes'])) $output .= "Planting Notes: {$r['planting_notes']}\n";
        if (!empty($r['planting_timing'])) $output .= "Timing: {$r['planting_timing']}\n";
        return $output;
    }
    
    protected function formatRotationResult(array $r): string
    {
        $output = "Previous Crop: {$r['previous_crop']} ({$r['previous_crop_family']})\n";
        $output .= "Following Crop: {$r['following_crop']} ({$r['following_crop_family']})\n";
        $output .= "Relationship: {$r['relationship']}\n";
        if (!empty($r['benefits'])) $output .= "Benefits: {$r['benefits']}\n";
        if (!empty($r['risks'])) $output .= "Risks: {$r['risks']}\n";
        if (!empty($r['minimum_gap_months'])) $output .= "Minimum Gap: {$r['minimum_gap_months']} months\n";
        if (!empty($r['soil_notes'])) $output .= "Soil Notes: {$r['soil_notes']}\n";
        return $output;
    }
    
    protected function formatCalendarResult(array $r): string
    {
        $output = "Crop: {$r['crop_name']} ({$r['crop_family']})\n";
        if (!empty($r['variety_type'])) $output .= "Variety: {$r['variety_type']}\n";
        if (!empty($r['indoor_seed_months'])) $output .= "Sow Indoors: {$r['indoor_seed_months']}\n";
        if (!empty($r['outdoor_seed_months'])) $output .= "Sow Outdoors: {$r['outdoor_seed_months']}\n";
        if (!empty($r['transplant_months'])) $output .= "Transplant: {$r['transplant_months']}\n";
        if (!empty($r['harvest_months'])) $output .= "Harvest: {$r['harvest_months']}\n";
        if ($r['frost_hardy']) $output .= "Frost Hardy: Yes\n";
        if (!empty($r['uk_specific_advice'])) $output .= "UK Advice: {$r['uk_specific_advice']}\n";
        return $output;
    }
}
