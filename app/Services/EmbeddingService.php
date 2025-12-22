<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected $ollamaHost;
    protected $model;
    protected $ollamaAvailable = null; // Cache health check result

    public function __construct()
    {
        // CORRECT PORT FOR EMBEDDINGS MODEL
        $this->ollamaHost = 'http://localhost:8007';
        $this->model = 'all-minilm:l6-v2';
    }

    /**
     * Check if Ollama embedding service is available
     */
    public function isOllamaAvailable(): bool
    {
        // Return cached result if available
        if ($this->ollamaAvailable !== null) {
            return $this->ollamaAvailable;
        }

        try {
            Log::info('Checking Ollama embeddings health', ['url' => $this->ollamaHost]);
            
            // Try to connect to Ollama with short timeout
            $response = Http::timeout(3)->get($this->ollamaHost . '/api/tags');
            
            $available = $response->successful();
            
            if ($available) {
                Log::info('Ollama embeddings service is available', [
                    'models' => $response->json()['models'] ?? []
                ]);
            } else {
                Log::warning('Ollama embeddings health check failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            
            // Cache the result
            $this->ollamaAvailable = $available;
            
            return $available;
            
        } catch (\Exception $e) {
            Log::warning('Ollama embeddings service is not available', [
                'error' => $e->getMessage(),
                'url' => $this->ollamaHost
            ]);
            
            // Cache the negative result
            $this->ollamaAvailable = false;
            
            return false;
        }
    }

    public function embed(string $text): ?array
    {
        // Check if Ollama is available before attempting
        if (!$this->isOllamaAvailable()) {
            Log::warning('Ollama embeddings service unavailable, skipping embedding generation');
            return null;
        }

        try {
            Log::info("Generating embedding with {$this->model} on port 8007");
            
            $response = Http::timeout(120)->post("{$this->ollamaHost}/api/embeddings", [
                'model' => $this->model,
                'prompt' => $text,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['embedding'] ?? null;
            }

            Log::error("Failed to generate embedding: " . $response->body());
            
            // Mark as unavailable after failure
            $this->ollamaAvailable = false;
            
            return null;
        } catch (\Exception $e) {
            Log::error("Embedding service error: " . $e->getMessage());
            
            // Mark as unavailable after error
            $this->ollamaAvailable = false;
            
            return null;
        }
    }
}
