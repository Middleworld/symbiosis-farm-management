<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Exception;
use Carbon\Carbon;

class FarmOSAuthService
{
    private static $instance = null;
    private $client;
    private $baseUrl;
    
    // Cache keys
    private const TOKEN_CACHE_KEY = 'farmos_access_token';
    private const EXPIRY_CACHE_KEY = 'farmos_token_expiry';
    
    private function __construct()
    {
        $this->baseUrl = Config::get('farmos.url', 'https://farmos.middleworldfarms.org');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
        ]);
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getAccessToken($forceRefresh = false)
    {
        // Force refresh if requested
        if ($forceRefresh) {
            Log::info('FarmOS token refresh forced');
            return $this->refreshAccessToken();
        }
        
        // Check if token is expired
        if ($this->isTokenExpired()) {
            Log::info('FarmOS token expired, refreshing');
            return $this->refreshAccessToken();
        }
        
        // Check cache
        $token = Cache::get(self::TOKEN_CACHE_KEY);
        if ($token) {
            return $token;
        }
        
        // Get new token if not in cache
        Log::info('FarmOS token not in cache, fetching new token');
        return $this->refreshAccessToken();
    }
    
    /**
     * Check if the current token is expired or about to expire
     */
    private function isTokenExpired(): bool
    {
        $expiry = Cache::get(self::EXPIRY_CACHE_KEY);
        
        if (!$expiry) {
            return true; // No expiry info, consider expired
        }
        
        // Consider expired if less than 5 minutes remaining
        $expiryTime = Carbon::parse($expiry);
        $now = Carbon::now();
        
        if ($now->greaterThanOrEqualTo($expiryTime->subMinutes(5))) {
            Log::info('FarmOS token expiring soon', [
                'expiry' => $expiryTime->toDateTimeString(),
                'now' => $now->toDateTimeString()
            ]);
            return true;
        }
        
        return false;
    }
    
    public function refreshAccessToken()
    {
        try {
            Log::info('Requesting new FarmOS access token');
            
            $response = $this->client->post('/oauth/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => Config::get('farmos.client_id'),
                    'client_secret' => Config::get('farmos.client_secret'),
                    'scope' => Config::get('farmos.oauth_scope', 'farmos_restws_access'),
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            $token = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? 3600; // Default 1 hour
            
            // Calculate actual expiry time
            $expiryTime = Carbon::now()->addSeconds($expiresIn);
            
            // Cache token until 5 minutes before expiry
            $cacheMinutes = max(1, floor($expiresIn / 60) - 5);
            Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addMinutes($cacheMinutes));
            
            // Store expiry time separately for checking
            Cache::put(self::EXPIRY_CACHE_KEY, $expiryTime->toDateTimeString(), now()->addMinutes($cacheMinutes + 10));
            
            Log::info('FarmOS access token refreshed successfully', [
                'expires_in' => $expiresIn,
                'expiry_time' => $expiryTime->toDateTimeString(),
                'cache_minutes' => $cacheMinutes
            ]);
            
            return $token;
        } catch (Exception $e) {
            Log::error('Failed to get FarmOS access token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to get FarmOS access token: ' . $e->getMessage());
        }
    }
    
    /**
     * Get time remaining until token expires
     */
    public function getTokenTimeRemaining(): ?int
    {
        $expiry = Cache::get(self::EXPIRY_CACHE_KEY);
        
        if (!$expiry) {
            return null;
        }
        
        $expiryTime = Carbon::parse($expiry);
        $now = Carbon::now();
        
        if ($now->greaterThanOrEqualTo($expiryTime)) {
            return 0;
        }
        
        return $now->diffInSeconds($expiryTime);
    }
    
    /**
     * Handle API call with automatic token refresh on 401 errors
     * 
     * @param callable $apiCall The API call to execute
     * @param int $maxRetries Maximum retry attempts
     * @return mixed The result of the API call
     */
    public function executeWithTokenRefresh(callable $apiCall, int $maxRetries = 2)
    {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $maxRetries) {
            try {
                return $apiCall();
            } catch (Exception $e) {
                $lastException = $e;
                
                // Check if it's a 401 Unauthorized error
                if (method_exists($e, 'getCode') && $e->getCode() === 401) {
                    Log::warning('FarmOS API returned 401, refreshing token', [
                        'attempt' => $attempt + 1,
                        'max_retries' => $maxRetries
                    ]);
                    
                    // Force token refresh and retry
                    $this->getAccessToken(true);
                    $attempt++;
                    continue;
                }
                
                // For other errors, throw immediately
                throw $e;
            }
        }
        
        // If all retries failed, throw the last exception
        Log::error('FarmOS API call failed after token refresh retries', [
            'attempts' => $attempt,
            'error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);
        throw $lastException ?? new Exception('API call failed after retries');
    }
    
    public function isAuthenticated()
    {
        try {
            $token = $this->getAccessToken();
            return !empty($token);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function authenticate()
    {
        try {
            $token = $this->getAccessToken();
            return !empty($token);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getAuthHeaders()
    {
        try {
            $token = $this->getAccessToken();
            return [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ];
        } catch (Exception $e) {
            return [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ];
        }
    }
}