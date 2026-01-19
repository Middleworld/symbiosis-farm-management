<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class QuickBooksService
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $companyId;
    protected $environment;

    public function __construct()
    {
        $this->environment = Setting::get('quickbooks_environment', 'sandbox');
        $this->baseUrl = $this->environment === 'production'
            ? 'https://quickbooks.api.intuit.com'
            : 'https://sandbox-quickbooks.api.intuit.com';

        $this->clientId = Setting::get('quickbooks_client_id');
        $this->clientSecret = Setting::get('quickbooks_client_secret');
        $this->companyId = Setting::get('quickbooks_company_id');
    }

    /**
     * Get OAuth2 authorization URL
     */
    public function getAuthorizationUrl()
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'com.intuit.quickbooks.accounting',
            'redirect_uri' => route('admin.quickbooks.callback'),
            'state' => csrf_token(),
        ];

        return 'https://appcenter.intuit.com/connect/oauth2?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code)
    {
        try {
            $response = Http::asForm()->post('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('admin.quickbooks.callback'),
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store tokens
                Setting::set('quickbooks_access_token', $data['access_token'], 'encrypted', 'QuickBooks Access Token');
                Setting::set('quickbooks_refresh_token', $data['refresh_token'], 'encrypted', 'QuickBooks Refresh Token');
                Setting::set('quickbooks_token_expires_at', now()->addSeconds($data['expires_in']), 'datetime', 'QuickBooks Token Expiry');

                return $data;
            }

            Log::error('QuickBooks token exchange failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('QuickBooks token exchange error', [
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
        $refreshToken = Setting::get('quickbooks_refresh_token');

        if (!$refreshToken) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Update tokens
                Setting::set('quickbooks_access_token', $data['access_token'], 'encrypted', 'QuickBooks Access Token');
                Setting::set('quickbooks_refresh_token', $data['refresh_token'], 'encrypted', 'QuickBooks Refresh Token');
                Setting::set('quickbooks_token_expires_at', now()->addSeconds($data['expires_in']), 'datetime', 'QuickBooks Token Expiry');

                return true;
            }

            Log::error('QuickBooks token refresh failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('QuickBooks token refresh error', [
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
        $token = Setting::get('quickbooks_access_token');
        $expiresAt = Setting::get('quickbooks_token_expires_at');

        if (!$token || ($expiresAt && now()->isAfter($expiresAt))) {
            if (!$this->refreshAccessToken()) {
                return null;
            }
            $token = Setting::get('quickbooks_access_token');
        }

        return $token;
    }

    /**
     * Make authenticated API request
     */
    protected function makeRequest($method, $endpoint, $data = null)
    {
        $token = $this->getValidAccessToken();

        if (!$token) {
            throw new \Exception('No valid QuickBooks access token available');
        }

        $url = $this->baseUrl . '/v3/company/' . $this->companyId . $endpoint;

        $request = Http::withToken($token);

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
                $request = Http::withToken($token);

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

        Log::error('QuickBooks API request failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('QuickBooks API request failed: ' . $response->body());
    }

    /**
     * Test connection to QuickBooks
     */
    public function testConnection()
    {
        try {
            $response = $this->makeRequest('GET', '/companyinfo/' . $this->companyId);
            return [
                'success' => true,
                'company' => $response['QueryResponse']['CompanyInfo'][0]['CompanyName'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get accounts from QuickBooks
     */
    public function getAccounts()
    {
        try {
            $response = $this->makeRequest('GET', '/query?query=SELECT * FROM Account');
            return $response['QueryResponse']['Account'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get QuickBooks accounts', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Sync transactions to QuickBooks
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
                // Convert transaction to QuickBooks format
                $qbTransaction = $this->convertTransactionToQuickBooks($transaction);

                // Create transaction in QuickBooks
                $response = $this->makeRequest('POST', '/purchase', $qbTransaction);

                if (isset($response['Purchase'])) {
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
     * Convert internal transaction to QuickBooks format
     */
    protected function convertTransactionToQuickBooks($transaction)
    {
        // This is a simplified conversion - would need to be expanded based on actual transaction structure
        return [
            'TxnDate' => $transaction['date'],
            'TotalAmt' => abs($transaction['amount']),
            'Line' => [
                [
                    'Amount' => abs($transaction['amount']),
                    'DetailType' => 'AccountBasedExpenseLineDetail',
                    'AccountBasedExpenseLineDetail' => [
                        'AccountRef' => [
                            'value' => $this->findOrCreateAccount($transaction['category'])
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Find or create account in QuickBooks
     */
    protected function findOrCreateAccount($categoryName)
    {
        // Simplified - would need proper account mapping
        $accounts = $this->getAccounts();

        foreach ($accounts as $account) {
            if ($account['Name'] === $categoryName) {
                return $account['Id'];
            }
        }

        // Create new account if not found
        try {
            $accountData = [
                'Name' => $categoryName,
                'AccountType' => 'Expense',
                'AccountSubType' => 'SuppliesMaterialsCogs'
            ];

            $response = $this->makeRequest('POST', '/account', $accountData);
            return $response['Account']['Id'];
        } catch (\Exception $e) {
            Log::error('Failed to create QuickBooks account', [
                'category' => $categoryName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}