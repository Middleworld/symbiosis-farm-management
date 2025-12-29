<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SymbiosisAIService
{
    protected $apiKey;
    protected $baseUrl;
    protected $ollamaAvailable = null; // Cache health check result

    public function __construct()
    {
        $this->apiKey = null; // Not needed for local Ollama
        $this->baseUrl = 'http://localhost:8005/api'; // Using Phi-3 3B model on port 8005
    }

    /**
     * Check if Ollama service is available
     */
    public function isOllamaAvailable(): bool
    {
        // Return cached result if available (valid for 5 minutes)
        if ($this->ollamaAvailable !== null) {
            return $this->ollamaAvailable;
        }

        try {
            Log::info('Checking AI service health', ['url' => $this->baseUrl]);
            
            // Try to connect to AI service with short timeout
            // Check root endpoint instead of /tags (works for both Ollama and custom service)
            $response = Http::timeout(3)->get(rtrim($this->baseUrl, '/api'));
            
            $available = $response->successful();
            
            if ($available) {
                $body = $response->json();
                Log::info('AI service is available', ['response' => $body]);
            } else {
                Log::warning('AI service health check failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            
            // Cache the result
            $this->ollamaAvailable = $available;
            
            return $available;
            
        } catch (\Exception $e) {
            Log::warning('Ollama is not available', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl
            ]);
            
            // Cache the negative result
            $this->ollamaAvailable = false;
            
            return false;
        }
    }

    /**
     * Process a chat completion request using Claude API or Ollama
     * Automatically falls back to Claude if Ollama is unavailable
     */
    public function chat(array $messages, array $options = []): array
    {
        // Check if explicitly requesting Claude
        if (isset($options['provider']) && $options['provider'] === 'anthropic') {
            return $this->chatWithClaude($messages, $options);
        }
        
        // Check if Ollama is available
        if (!$this->isOllamaAvailable()) {
            Log::info('Ollama unavailable, falling back to Claude API');
            
            // Check if Claude is configured
            $claudeKey = $options['api_key'] ?? env('CLAUDE_API_KEY');
            if ($claudeKey && $claudeKey !== 'your_claude_api_key_here') {
                try {
                    return $this->chatWithClaude($messages, $options);
                } catch (\Exception $e) {
                    Log::error('Claude fallback also failed', ['error' => $e->getMessage()]);
                    throw new \Exception('Both Ollama and Claude are unavailable: ' . $e->getMessage());
                }
            } else {
                throw new \Exception('Ollama is unavailable and Claude API key is not configured');
            }
        }
        
        // Try Ollama with fallback to Claude on failure
        try {
            return $this->chatWithOllama($messages, $options);
        } catch (\Exception $e) {
            Log::warning('Ollama request failed, attempting Claude fallback', [
                'error' => $e->getMessage()
            ]);
            
            // Mark Ollama as unavailable
            $this->ollamaAvailable = false;
            
            // Try Claude if configured
            $claudeKey = $options['api_key'] ?? env('CLAUDE_API_KEY');
            if ($claudeKey && $claudeKey !== 'your_claude_api_key_here') {
                return $this->chatWithClaude($messages, $options);
            }
            
            // Re-throw original error if no fallback available
            throw $e;
        }
    }
    
    /**
     * Chat with Claude API (Anthropic)
     */
    private function chatWithClaude(array $messages, array $options = []): array
    {
        try {
            $apiKey = $options['api_key'] ?? env('CLAUDE_API_KEY');
            $model = $options['model'] ?? 'claude-3-5-sonnet-20241022';
            
            if (!$apiKey || $apiKey === 'your_claude_api_key_here') {
                throw new \Exception('Claude API key not configured');
            }
            
            // Separate system message from conversation messages
            $systemMessage = '';
            $conversationMessages = [];
            
            foreach ($messages as $message) {
                if ($message['role'] === 'system') {
                    $systemMessage = $message['content'];
                } else {
                    $conversationMessages[] = $message;
                }
            }
            
            $payload = [
                'model' => $model,
                'max_tokens' => $options['max_tokens'] ?? 1024,
                'temperature' => $options['temperature'] ?? 0.1,
                'messages' => $conversationMessages
            ];
            
            if ($systemMessage) {
                $payload['system'] = $systemMessage;
            }
            
            Log::info('Claude API request', [
                'model' => $model,
                'messages_count' => count($conversationMessages)
            ]);
            
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json'
                ])
                ->post('https://api.anthropic.com/v1/messages', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? 'No response generated.';
                
                Log::info('Claude API response', [
                    'usage' => $data['usage'] ?? []
                ]);
                
                // Return in OpenAI-compatible format
                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => $content
                            ]
                        ]
                    ],
                    'usage' => $data['usage'] ?? []
                ];
            }
            
            Log::error('Claude API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('Claude API request failed: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('Claude API error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Chat with Ollama (local models)
     */
    private function chatWithOllama(array $messages, array $options = []): array
    {
        try {
            // Convert messages to a single prompt for Ollama
            $prompt = $this->convertMessagesToPrompt($messages);
            
            // Allow override of base URL and model via options
            $baseUrl = $options['base_url'] ?? $this->baseUrl;
            $model = $options['model'] ?? 'phi3:latest';
            
            // Use longer timeout for Mistral 7B (CPU-only, needs 90+ seconds)
            $timeout = ($model === 'mistral:7b') ? 180 : 120;
            
            $response = Http::timeout($timeout)->post($baseUrl . '/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => $options['temperature'] ?? 0.3,
                    'num_predict' => $options['max_tokens'] ?? 500,
                    'top_p' => 0.9,
                    'top_k' => 40,
                ]
            ]);

            Log::info('Ollama API request', [
                'url' => $baseUrl . '/generate',
                'model' => $model,
                'prompt_length' => strlen($prompt)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => $data['response'] ?? 'No response generated.'
                            ]
                        ]
                    ]
                ];
            }

            Log::error('Ollama API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $baseUrl . '/generate'
            ]);

            throw new \Exception('Ollama API request failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Ollama chat error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert OpenAI-style messages to a single prompt for Ollama
     */
    private function convertMessagesToPrompt(array $messages): string
    {
        $prompt = '';
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';
            
            // Handle array content (convert to string)
            if (is_array($content)) {
                $content = json_encode($content);
            }
            
            if ($role === 'system') {
                $prompt .= "System: {$content}\n\n";
            } elseif ($role === 'user') {
                $prompt .= "User: {$content}\n\n";
            } elseif ($role === 'assistant') {
                $prompt .= "Assistant: {$content}\n\n";
            }
        }
        
        return trim($prompt);
    }

    /**
     * Generate farming insights based on data
     */
    public function generateFarmingInsights(array $farmData): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert agricultural AI assistant specializing in sustainable farming practices, crop planning, and farm management. Provide practical, actionable insights.'
            ],
            [
                'role' => 'user',
                'content' => 'Analyze this farm data and provide insights: ' . json_encode($farmData)
            ]
        ];

        $response = $this->chat($messages);
        return $response['choices'][0]['message']['content'] ?? 'Unable to generate insights.';
    }

    /**
     * Crop planning assistance
     */
    public function suggestCropPlanning(array $conditions): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a crop planning specialist. Analyze soil, weather, and market conditions to suggest optimal crop planning strategies.'
            ],
            [
                'role' => 'user',
                'content' => 'Based on these conditions, suggest a crop planning strategy: ' . json_encode($conditions)
            ]
        ];

        $response = $this->chat($messages);
        return $response['choices'][0]['message']['content'] ?? 'Unable to generate crop planning suggestions.';
    }

    /**
     * Pest and disease identification
     */
    public function identifyPestOrDisease(string $description, array $images = []): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an agricultural pathology expert. Help identify pests and diseases based on descriptions and suggest treatment options.'
            ],
            [
                'role' => 'user',
                'content' => "Help identify this pest or disease issue: {$description}"
            ]
        ];

        $response = $this->chat($messages);
        return $response['choices'][0]['message']['content'] ?? 'Unable to identify the issue.';
    }

    /**
     * Check if the AI service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/tags');
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('AI service availability check failed: ' . $e->getMessage());
            return false;
        }
    }
}
