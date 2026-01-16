<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Exception;
use Carbon\Carbon;

class MetOfficeAuthService
{
    private static $instance = null;
    private $client;
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    // Cache keys
    private const ACCESS_TOKEN_CACHE_KEY = 'met_office_access_token';
    private const REFRESH_TOKEN_CACHE_KEY = 'met_office_refresh_token';
    private const EXPIRY_CACHE_KEY = 'met_office_token_expiry';

    private function __construct()
    {
        // Met Office DataHub OAuth2 endpoints
        $this->baseUrl = 'https://datahub.metoffice.gov.uk';
        $this->clientId = Config::get('metoffice.client_id', env('MET_OFFICE_CLIENT_ID'));
        $this->clientSecret = Config::get('metoffice.client_secret', env('MET_OFFICE_CLIENT_SECRET'));
        $this->redirectUri = Config::get('metoffice.redirect_uri', env('MET_OFFICE_REDIRECT_URI', url('/admin/metoffice/callback')));

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

    /**
     * Get authorization URL for user consent
     */
    public function getAuthorizationUrl($state = null)
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'scope' => 'openid offline_access', // Request refresh token
            'redirect_uri' => $this->redirectUri,
            'state' => $state ?: bin2hex(random_bytes(16))
        ];

        $query = http_build_query($params);
        return 'https://login.auth.metoffice.cloud/dce84ec6-ce0f-45d1-ba16-e36b817081eb/oauth2/v2.0/authorize?' . $query;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken($code)
    {
        try {
            Log::info('Exchanging Met Office authorization code for tokens');

            $response = $this->client->post('/oauth2/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['access_token'])) {
                throw new Exception('No access token in response');
            }

            $this->storeTokens($data);

            Log::info('Met Office tokens obtained successfully');
            return $data;

        } catch (Exception $e) {
            Log::error('Failed to exchange Met Office code for token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to get Met Office access token: ' . $e->getMessage());
        }
    }

    /**
     * Get access token (refresh if needed)
     */
    public function getAccessToken($forceRefresh = false)
    {
        if ($forceRefresh) {
            Log::info('Met Office token refresh forced');
            return $this->refreshAccessToken();
        }

        if ($this->isTokenExpired()) {
            Log::info('Met Office token expired, refreshing');
            return $this->refreshAccessToken();
        }

        $token = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);
        if ($token) {
            return $token;
        }

        // Try to refresh if we have a refresh token
        return $this->refreshAccessToken();
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken()
    {
        $refreshToken = Cache::get(self::REFRESH_TOKEN_CACHE_KEY);

        if (!$refreshToken) {
            throw new Exception('No refresh token available - user needs to re-authorize');
        }

        try {
            Log::info('Refreshing Met Office access token');

            $response = $this->client->post('/oauth2/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['access_token'])) {
                throw new Exception('No access token in refresh response');
            }

            $this->storeTokens($data);

            Log::info('Met Office access token refreshed successfully');
            return $data['access_token'];

        } catch (Exception $e) {
            Log::error('Failed to refresh Met Office access token', [
                'error' => $e->getMessage()
            ]);

            // Clear cached tokens on refresh failure
            $this->clearTokens();

            throw new Exception('Failed to refresh Met Office access token: ' . $e->getMessage());
        }
    }

    /**
     * Store tokens in cache
     */
    private function storeTokens($tokenData)
    {
        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = $tokenData['expires_in'] ?? 3600;

        $expiryTime = Carbon::now()->addSeconds($expiresIn);

        // Cache access token until 5 minutes before expiry
        $cacheMinutes = max(1, floor($expiresIn / 60) - 5);
        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $accessToken, now()->addMinutes($cacheMinutes));

        if ($refreshToken) {
            // Cache refresh token for longer (typically 30 days)
            Cache::put(self::REFRESH_TOKEN_CACHE_KEY, $refreshToken, now()->addDays(30));
        }

        // Store expiry time
        Cache::put(self::EXPIRY_CACHE_KEY, $expiryTime->toDateTimeString(), now()->addMinutes($cacheMinutes + 10));

        Log::info('Met Office tokens cached', [
            'expires_in' => $expiresIn,
            'expiry_time' => $expiryTime->toDateTimeString(),
            'cache_minutes' => $cacheMinutes
        ]);
    }

    /**
     * Check if token is expired
     */
    private function isTokenExpired(): bool
    {
        $expiry = Cache::get(self::EXPIRY_CACHE_KEY);

        if (!$expiry) {
            return true;
        }

        $expiryTime = Carbon::parse($expiry);
        $now = Carbon::now();

        // Consider expired if less than 5 minutes remaining
        if ($now->greaterThanOrEqualTo($expiryTime->subMinutes(5))) {
            Log::info('Met Office token expiring soon', [
                'expiry' => $expiryTime->toDateTimeString(),
                'now' => $now->toDateTimeString()
            ]);
            return true;
        }

        return false;
    }

    /**
     * Clear cached tokens
     */
    public function clearTokens()
    {
        Cache::forget(self::ACCESS_TOKEN_CACHE_KEY);
        Cache::forget(self::REFRESH_TOKEN_CACHE_KEY);
        Cache::forget(self::EXPIRY_CACHE_KEY);

        Log::info('Met Office cached tokens cleared');
    }

    /**
     * Check if user is authorized
     */
    public function isAuthorized(): bool
    {
        try {
            $this->getAccessToken();
            return true;
        } catch (Exception $e) {
            return false;
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
}