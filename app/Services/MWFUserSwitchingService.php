<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MWFUserSwitchingService
{
    private string $baseUrl = 'https://middleworldfarms.org/wp-json/mwf/v1/';
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('MWF_API_KEY', 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h');
    }

    /**
     * Search for users by name, email, or username
     */
    public function searchUsers(string $query, int $limit = 10): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . 'users/search', [
                'search' => $query,
                'limit' => $limit
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['users'] ?? [];
            }

            Log::error('Failed to search users', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception searching users', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Get recently active users
     */
    public function getRecentUsers(int $limit = 20): array
    {
        try {
            $response = Http::withHeaders([
                'X-WC-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . 'users/recent', [
                'limit' => $limit
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['users'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Exception getting recent users', [
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
            ])->get($this->baseUrl . "users/{$userId}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['user'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting user by ID', [
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
            ])->post($this->baseUrl . 'users/switch', [
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'admin_context' => $adminContext
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['switch_url'] ?? null;
            }

            Log::error('Failed to create user switch URL', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception creating user switch URL', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Validate a switch token
     */
    public function validateSwitchToken(string $token): bool
    {
        try {
            $response = Http::get($this->baseUrl . 'users/switch/validate', [
                'token' => $token
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['success'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Exception validating switch token', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get user funds balance - DISABLED
     * Account Funds plugin removed from system
     */
    public function getUserFunds(string $email): float
    {
        return 0;
    }
}
