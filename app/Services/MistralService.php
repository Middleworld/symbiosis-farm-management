<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralService
{
    protected $baseUrl;
    protected $model;

    public function __construct()
    {
        $this->baseUrl = 'http://localhost:8006';
        $this->model = 'mistral'; // or whatever the model name is
    }

    /**
     * Analyze subscription data for issues
     */
    public function analyzeSubscriptions(array $subscriptionData): array
    {
        $prompt = $this->buildSubscriptionAnalysisPrompt($subscriptionData);
        
        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert business analyst specializing in subscription services and e-commerce. Analyze the provided data and identify issues, risks, and recommendations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'analysis' => $result['choices'][0]['message']['content'] ?? 'No analysis generated',
                    'issues_found' => $this->extractIssues($result['choices'][0]['message']['content'] ?? ''),
                    'recommendations' => $this->extractRecommendations($result['choices'][0]['message']['content'] ?? '')
                ];
            } else {
                Log::error('Mistral API error', ['response' => $response->body()]);
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Mistral service error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Analyze product data for issues
     */
    public function analyzeProducts(array $productData): array
    {
        $prompt = $this->buildProductAnalysisPrompt($productData);
        
        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert e-commerce product analyst. Analyze product data for pricing issues, stock problems, and business recommendations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 800
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'analysis' => $result['choices'][0]['message']['content'] ?? 'No analysis generated',
                    'issues_found' => $this->extractIssues($result['choices'][0]['message']['content'] ?? ''),
                    'recommendations' => $this->extractRecommendations($result['choices'][0]['message']['content'] ?? '')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate business insights from combined data
     */
    public function generateBusinessInsights(array $data): array
    {
        $prompt = "Analyze this business data and provide key insights:\n\n" . json_encode($data, JSON_PRETTY_PRINT);
        
        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a business intelligence analyst. Provide actionable insights from the data provided.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.4,
                'max_tokens' => 600
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'insights' => $result['choices'][0]['message']['content'] ?? 'No insights generated'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Service error: ' . $e->getMessage()
            ];
        }
    }

    private function buildSubscriptionAnalysisPrompt(array $data): string
    {
        $prompt = "Analyze this subscription data for issues:\n\n";
        $prompt .= "Total Active Subscriptions: " . ($data['total_subscriptions'] ?? 0) . "\n";
        $prompt .= "Subscriptions with Renewal Scheduling: " . ($data['with_scheduling'] ?? 0) . "\n";
        $prompt .= "Broken Subscriptions (no renewal scheduling): " . ($data['broken_count'] ?? 0) . "\n\n";
        
        if (!empty($data['broken_subscriptions'])) {
            $prompt .= "Broken Subscriptions Details:\n";
            foreach ($data['broken_subscriptions'] as $sub) {
                $prompt .= "- ID: {$sub['id']}, Created: {$sub['created']}, Customer: {$sub['customer']}, Amount: £{$sub['amount']}\n";
            }
        }
        
        $prompt .= "\nRevenue Impact:\n";
        $prompt .= "- Estimated lost revenue: £" . ($data['estimated_loss'] ?? 0) . "\n";
        $prompt .= "- Affected customers: " . ($data['affected_customers'] ?? 0) . "\n\n";
        
        $prompt .= "Please identify:\n";
        $prompt .= "1. Critical issues requiring immediate action\n";
        $prompt .= "2. Revenue recovery strategies\n";
        $prompt .= "3. System improvements needed\n";
        $prompt .= "4. Monitoring recommendations\n";
        
        return $prompt;
    }

    private function buildProductAnalysisPrompt(array $data): string
    {
        $prompt = "Analyze this product data:\n\n";
        $prompt .= json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Please identify:\n";
        $prompt .= "1. Pricing anomalies or issues\n";
        $prompt .= "2. Stock/availability problems\n";
        $prompt .= "3. Product performance insights\n";
        $prompt .= "4. Recommendations for optimization\n";
        
        return $prompt;
    }

    private function extractIssues(string $analysis): array
    {
        // Simple extraction - look for keywords indicating issues
        $issues = [];
        $lines = explode("\n", $analysis);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'issue') !== false || 
                stripos($line, 'problem') !== false || 
                stripos($line, 'error') !== false ||
                stripos($line, 'broken') !== false ||
                stripos($line, 'failed') !== false) {
                $issues[] = $line;
            }
        }
        
        return array_slice($issues, 0, 5); // Limit to 5 issues
    }

    private function extractRecommendations(string $analysis): array
    {
        // Simple extraction - look for recommendation keywords
        $recommendations = [];
        $lines = explode("\n", $analysis);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'recommend') !== false || 
                stripos($line, 'should') !== false || 
                stripos($line, 'consider') !== false ||
                stripos($line, 'implement') !== false ||
                stripos($line, 'monitor') !== false) {
                $recommendations[] = $line;
            }
        }
        
        return array_slice($recommendations, 0, 5); // Limit to 5 recommendations
    }
}
