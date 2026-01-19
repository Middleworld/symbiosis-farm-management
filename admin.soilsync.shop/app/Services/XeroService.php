<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class XeroService
{
    protected $baseUrl = 'https://api.xero.com/api.xro/2.0';
    protected $clientId;
    protected $clientSecret;
    protected $tenantId;
    protected $environment;

    public function __construct()
    {
        $this->environment = Setting::get('xero_environment', 'demo');
        $this->clientId = Setting::get('xero_client_id');
        $this->clientSecret = Setting::get('xero_client_secret');
        $this->tenantId = Setting::get('xero_tenant_id');
    }

    /**
     * Get OAuth2 authorization URL
     */
    public function getAuthorizationUrl()
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'accounting.transactions accounting.contacts',
            'redirect_uri' => route('admin.xero.callback'),
            'state' => csrf_token(),
        ];

        return 'https://login.xero.com/identity/connect/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code)
    {
        try {
            $response = Http::asForm()->post('https://identity.xero.com/connect/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('admin.xero.callback'),
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store tokens
                Setting::set('xero_access_token', $data['access_token'], 'encrypted', 'Xero Access Token');
                Setting::set('xero_refresh_token', $data['refresh_token'], 'encrypted', 'Xero Refresh Token');
                Setting::set('xero_token_expires_at', now()->addSeconds($data['expires_in']), 'datetime', 'Xero Token Expiry');

                // Store tenant ID if not set
                if (!Setting::get('xero_tenant_id') && isset($data['tenant_id'])) {
                    Setting::set('xero_tenant_id', $data['tenant_id'], 'string', 'Xero Tenant ID');
                }

                return $data;
            }

            Log::error('Xero token exchange failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Xero token exchange error', [
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
        $refreshToken = Setting::get('xero_refresh_token');

        if (!$refreshToken) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://identity.xero.com/connect/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Update tokens
                Setting::set('xero_access_token', $data['access_token'], 'encrypted', 'Xero Access Token');
                Setting::set('xero_refresh_token', $data['refresh_token'], 'encrypted', 'Xero Refresh Token');
                Setting::set('xero_token_expires_at', now()->addSeconds($data['expires_in']), 'datetime', 'Xero Token Expiry');

                return true;
            }

            Log::error('Xero token refresh failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Xero token refresh error', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get valid access token (refresh if needed)
     */
    protected function getValidAccessToken()
    {
        $token = Setting::get('xero_access_token');
        $expiresAt = Setting::get('xero_token_expires_at');

        if (!$token || ($expiresAt && now()->isAfter($expiresAt))) {
            if (!$this->refreshAccessToken()) {
                return null;
            }
            $token = Setting::get('xero_access_token');
        }

        return $token;
    }

    /**
     * Make authenticated API request
     */
    protected function makeRequest($method, $endpoint, $data = null)
    {
        $token = $this->getValidAccessToken();
        $tenantId = Setting::get('xero_tenant_id');

        if (!$token || !$tenantId) {
            throw new \Exception('No valid Xero access token or tenant ID available');
        }

        $url = $this->baseUrl . $endpoint;

        $request = Http::withToken($token)->withHeader('Xero-tenant-id', $tenantId);

        if ($method === 'GET') {
            $response = $request->get($url);
        } elseif ($method === 'POST') {
            $response = $request->post($url, $data);
        } elseif ($method === 'PUT') {
            $response = $request->put($url, $data);
        }

        if ($response->successful()) {
            return $response->json();
        }

        // Check if token expired
        if ($response->status() === 401) {
            // Try refreshing token once
            if ($this->refreshAccessToken()) {
                $token = $this->getValidAccessToken();
                $request = Http::withToken($token)->withHeader('Xero-tenant-id', $tenantId);

                if ($method === 'GET') {
                    $response = $request->get($url);
                } elseif ($method === 'POST') {
                    $response = $request->post($url, $data);
                } elseif ($method === 'PUT') {
                    $response = $request->put($url, $data);
                }

                if ($response->successful()) {
                    return $response->json();
                }
            }
        }

        Log::error('Xero API request failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Xero API request failed: ' . $response->body());
    }

    /**
     * Test connection to Xero
     */
    public function testConnection()
    {
        try {
            $response = $this->makeRequest('GET', '/Organisation');
            $org = $response['Organisations'][0] ?? null;

            return [
                'success' => true,
                'organisation' => $org['Name'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get accounts from Xero
     */
    public function getAccounts()
    {
        try {
            $response = $this->makeRequest('GET', '/Accounts');
            return $response['Accounts'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get Xero accounts', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Sync transactions to Xero
     */
    public function syncTransactions($transactions)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($transactions as $transaction) {
            try {
                // Convert transaction to Xero format
                $xeroTransaction = $this->convertTransactionToXero($transaction);

                // Create transaction in Xero
                $response = $this->makeRequest('POST', '/BankTransactions', ['BankTransactions' => [$xeroTransaction]]);

                if (isset($response['BankTransactions'])) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = 'Failed to create transaction: ' . json_encode($response);
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Convert internal transaction to Xero format
     */
    protected function convertTransactionToXero($transaction)
    {
        // This is a simplified conversion - would need to be expanded based on actual transaction structure
        return [
            'Type' => $transaction['amount'] < 0 ? 'SPEND' : 'RECEIVE',
            'Contact' => [
                'Name' => $transaction['contact'] ?? 'Farm Transaction'
            ],
            'Date' => $transaction['date'],
            'LineAmountTypes' => 'Exclusive',
            'LineItems' => [
                [
                    'Description' => $transaction['description'] ?? 'Farm transaction',
                    'Quantity' => 1,
                    'UnitAmount' => abs($transaction['amount']),
                    'AccountCode' => $this->findOrCreateAccount($transaction['category'])
                ]
            ]
        ];
    }

    /**
     * Find or create account in Xero
     */
    protected function findOrCreateAccount($categoryName)
    {
        // Simplified - would need proper account mapping
        $accounts = $this->getAccounts();

        foreach ($accounts as $account) {
            if ($account['Name'] === $categoryName) {
                return $account['Code'];
            }
        }

        // Create new account if not found
        try {
            $accountData = [
                'Code' => strtoupper(substr($categoryName, 0, 10)), // Xero account codes are short
                'Name' => $categoryName,
                'Type' => 'EXPENSE',
                'Status' => 'ACTIVE'
            ];

            $response = $this->makeRequest('POST', '/Accounts', ['Accounts' => [$accountData]]);
            return $response['Accounts'][0]['Code'];
        } catch (\Exception $e) {
            Log::error('Failed to create Xero account', [
                'category' => $categoryName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}