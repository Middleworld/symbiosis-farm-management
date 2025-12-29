<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WordPressUserService
{
    private $apiKey;
    private $apiBase;
    private $baseUrl;
    
    public function __construct()
    {
        $this->apiKey = config('services.wordpress.api_key');
        $this->apiBase = config('services.wordpress.api_base');
        $this->baseUrl = config('services.wordpress.base_url');
    }
    
    /**
     * Search for WordPress users
     */
    public function searchUsers(string $query, int $limit = 20, string $role = 'customer'): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->apiBase . '/users/search', [
                'q' => $query,
                'limit' => $limit,
                'role' => $role
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['success' => false, 'message' => 'API request failed', 'users' => []];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => 'Network error: ' . $e->getMessage(), 'users' => []];
        }
    }
    
    /**
     * Get detailed information about a specific user
     */
    public function getUserDetails(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->apiBase . "/users/{$userId}");
            
            return $response->successful() ? $response->json() : ['success' => false];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get recent WordPress users
     */
    public function getRecentUsers(int $limit = 20, string $role = 'customer'): array
    {
        $cacheKey = "wp_recent_users_{$limit}_{$role}";
        
        return Cache::remember($cacheKey, 300, function () use ($limit, $role) { // 5 minute cache
            try {
                $response = Http::withHeaders([
                    'X-WC-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])->get($this->apiBase . '/users/recent', [
                    'limit' => $limit,
                    'role' => $role
                ]);
                
                return $response->successful() ? $response->json() : ['success' => false, 'users' => []];
                
            } catch (RequestException $e) {
                return ['success' => false, 'message' => $e->getMessage(), 'users' => []];
            }
        });
    }
    
    /**
     * Switch to a WordPress user and get preview URL
     */
    public function switchToUser(int $userId, string $redirectTo = '/my-account/', string $context = 'admin'): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiBase . '/users/switch', [
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'admin_context' => $context
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Ensure preview_url is absolute
                if (isset($data['preview_url']) && !str_starts_with($data['preview_url'], 'http')) {
                    $data['preview_url'] = $this->baseUrl . $data['preview_url'];
                }
                
                return $data;
            }
            
            return ['success' => false, 'message' => 'Switch request failed'];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => 'Network error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate a preview token
     */
    public function validateToken(string $token): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->apiBase . '/users/switch/validate', [
                'token' => $token
            ]);
            
            return $response->successful() ? $response->json() : ['success' => false];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->apiBase . '/users/recent', ['limit' => 1]);
            
            if ($response->successful()) {
                return ['success' => true, 'message' => 'API connection successful'];
            }
            
            return ['success' => false, 'message' => 'API connection failed: ' . $response->status()];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => 'Connection error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Access customer profile using dedicated endpoint
     */
    public function customerProfile(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiBase . '/customer/profile', [
                'user_id' => $userId
            ]);
            
            return $response->successful() ? $response->json() : ['success' => false, 'message' => 'API request failed'];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Access customer subscriptions using dedicated endpoint
     */
    public function customerSubscriptions(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiBase . '/customer/subscriptions', [
                'user_id' => $userId
            ]);
            
            return $response->successful() ? $response->json() : ['success' => false, 'message' => 'API request failed'];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Access customer orders using dedicated endpoint
     */
    public function customerOrders(int $userId): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->apiBase . '/customer/orders', [
                'user_id' => $userId
            ]);
            
            return $response->successful() ? $response->json() : ['success' => false, 'message' => 'API request failed'];
            
        } catch (RequestException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
