<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserSwitchingService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.wordpress.api_base', 'https://middleworldfarms.org/wp-json/mwf/v1');
        $this->apiKey = config('services.wordpress.api_key', 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h');
    }

    /**
     * Search for users by email or name
     */
    public function searchUsers(string $query, int $limit = 20): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/users/search', [
                'q' => $query,
                'limit' => $limit,
                'role' => 'customer'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['users'] ?? [];
            }

            Log::error('Failed to search users via MWF API', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception searching users via MWF API', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Get recent users
     */
    public function getRecentUsers(int $limit = 20): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/users/recent', [
                'limit' => $limit,
                'role' => 'customer'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['users'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Exception getting recent users via MWF API', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Get user details by ID
     */
    public function getUserById(int $userId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . "/users/{$userId}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['user'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting user by ID via MWF API', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Generate switch URL for a user
     */
    public function switchToUser(int $userId, string $redirectTo = '/my-account/', string $adminContext = 'admin_panel'): ?string
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/users/switch', [
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'admin_context' => $adminContext
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['switch_url'] ?? null;
            }

            Log::error('Failed to create user switch URL via MWF API', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception creating user switch URL via MWF API', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return null;
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
            ])->get($this->baseUrl . '/users/recent', ['limit' => 1]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'MWF API connection successful'];
            }

            return ['success' => false, 'message' => 'MWF API connection failed: ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'MWF API connection error: ' . $e->getMessage()];
        }
    }

    /**
     * Format user data for delivery tables
     */
    public function formatUserForDelivery(array $user): array
    {
        return [
            'id' => $user['id'],
            'name' => $user['display_name'],
            'email' => $user['email'],
            'switch_url' => $this->switchToUser($user['id']),
            'billing_city' => $user['wc_data']['billing_city'] ?? '',
            'total_spent' => $user['wc_data']['total_spent'] ?? '0.00',
            'order_count' => $user['wc_data']['order_count'] ?? 0
        ];
    }
}
