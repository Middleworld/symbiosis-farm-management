<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class CompaniesHouseService
{
    protected $baseUrl;
    protected $identityUrl;
    protected $clientId;
    protected $clientSecret;
    protected $companyNumber;

    public function __construct()
    {
        $this->baseUrl = config('services.companies_house.base_url', 'https://api.company-information.service.gov.uk');
        $this->identityUrl = 'https://identity.company-information.service.gov.uk';
        $this->clientId = Setting::get('companies_house_client_id', env('COMPANIES_HOUSE_CLIENT_ID'));
        $this->clientSecret = Setting::get('companies_house_client_secret', env('COMPANIES_HOUSE_CLIENT_SECRET'));
        $this->companyNumber = Setting::get('company_number', '13617115');
    }

    /**
     * Get OAuth2 authorization URL
     */
    public function getAuthorizationUrl()
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'https://identity.company-information.service.gov.uk/user/profile.read https://api.company-information.service.gov.uk/company.read',
            'redirect_uri' => config('services.companies_house.redirect_uri'),
            'state' => csrf_token(),
        ];

        return $this->identityUrl . '/oauth2/authorise?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code)
    {
        try {
            $response = Http::asForm()->post($this->identityUrl . '/oauth2/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.companies_house.redirect_uri'),
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store tokens
                Setting::set('companies_house_access_token', $data['access_token'], 'encrypted', 'Companies House Access Token');
                Setting::set('companies_house_refresh_token', $data['refresh_token'], 'encrypted', 'Companies House Refresh Token');
                Setting::set('companies_house_token_expires_at', now()->addSeconds($data['expires_in']), 'datetime', 'Companies House Token Expiry');

                return $data;
            }

            Log::error('Companies House token exchange failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Companies House token exchange error', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken()
    {
        $refreshToken = Setting::get('companies_house_refresh_token');

        if (!$refreshToken) {
            return false;
        }

        try {
            $response = Http::asForm()->post($this->identityUrl . '/oauth2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store new access token (refresh tokens are not returned on refresh)
                Setting::set('companies_house_access_token', $data['access_token'], 'encrypted', 'Companies House Access Token');
                Setting::set('companies_house_token_expires_at', now()->addSeconds($data['expires_in']), 'datetime', 'Companies House Token Expiry');

                return $data;
            }

            Log::error('Companies House token refresh failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Companies House token refresh error', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get valid access token (refresh if needed)
     */
    public function getValidAccessToken()
    {
        $token = Setting::get('companies_house_access_token');
        $expiresAt = Setting::get('companies_house_token_expires_at');

        // If token is expired or will expire in next 5 minutes, refresh it
        if (!$token || !$expiresAt || now()->addMinutes(5)->isAfter($expiresAt)) {
            if (!$this->refreshAccessToken()) {
                return null;
            }
            $token = Setting::get('companies_house_access_token');
        }

        return $token;
    }

    /**
     * Make authenticated API request
     */
    public function makeApiRequest($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->baseUrl . $endpoint;
        $token = $this->getValidAccessToken();

        if ($token) {
            $httpClient = Http::withToken($token);
        } elseif ($this->hasApiKey()) {
            $httpClient = Http::withBasicAuth($this->clientId, '');
        } else {
            throw new \Exception('No Companies House credentials configured');
        }

        try {
            switch ($method) {
                case 'GET':
                    $response = $httpClient->get($url);
                    break;
                case 'POST':
                    $response = $httpClient->post($url, $data);
                    break;
                case 'PUT':
                    $response = $httpClient->put($url, $data);
                    break;
                case 'DELETE':
                    $response = $httpClient->delete($url);
                    break;
                default:
                    throw new \Exception("Unsupported HTTP method: {$method}");
            }

            if ($response->successful()) {
                return $response->json();
            }

            // If unauthorized, try refreshing token once
            if ($response->status() === 401) {
                if ($this->refreshAccessToken()) {
                    $token = $this->getValidAccessToken();
                    $httpClient = Http::withToken($token);

                    switch ($method) {
                        case 'GET':
                            $response = $httpClient->get($url);
                            break;
                        case 'POST':
                            $response = $httpClient->post($url, $data);
                            break;
                        case 'PUT':
                            $response = $httpClient->put($url, $data);
                            break;
                        case 'DELETE':
                            $response = $httpClient->delete($url);
                            break;
                    }

                    if ($response->successful()) {
                        return $response->json();
                    }
                }
            }

            Log::error('Companies House API request failed', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Companies House API request error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get company data
     */
    public function getCompanyData($companyNumber = null)
    {
        $companyNum = $companyNumber ?: $this->companyNumber;
        return $this->makeApiRequest("/company/{$companyNum}");
    }

    /**
     * Get company officers
     */
    public function getOfficers($companyNumber = null)
    {
        $companyNum = $companyNumber ?: $this->companyNumber;
        $result = $this->makeApiRequest("/company/{$companyNum}/officers");

        if ($result && isset($result['items'])) {
            return $result['items'];
        }

        return [];
    }

    /**
     * Get filing history
     */
    public function getFilingHistory($companyNumber = null)
    {
        $companyNum = $companyNumber ?: $this->companyNumber;
        $result = $this->makeApiRequest("/company/{$companyNum}/filing-history");

        if ($result && isset($result['items'])) {
            return $result['items'];
        }

        return [];
    }

    /**
     * Check if API key is configured
     */
    public function hasApiKey()
    {
        return !empty($this->clientId);
    }

    /**
     * Check if OAuth is configured
     */
    public function hasOAuthConfigured()
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        if ($this->hasOAuthConfigured()) {
            return !empty($this->getValidAccessToken());
        }

        return $this->hasApiKey();
    }
}