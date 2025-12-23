<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenBankingService
{
    private Client $client;
    private string $certificatePath;
    private string $privateKeyPath;
    private string $passphrase;
    private string $ssa;
    private string $clientId;
    private string $redirectUri;
    private string $environment;

    public function __construct()
    {
        $this->certificatePath = storage_path(config('openbanking.certificate_path'));
        $this->privateKeyPath = storage_path(config('openbanking.private_key_path'));
        $this->passphrase = config('openbanking.key_passphrase');
        $this->ssa = config('openbanking.ssa');
        $this->clientId = config('openbanking.client_id');
        $this->redirectUri = config('openbanking.redirect_uri');
        $this->environment = config('openbanking.environment');

        // Initialize Guzzle client with mTLS certificates
        // Note: Using decrypted private key for Guzzle compatibility
        // Disable SSL verification in sandbox due to self-signed certs
        $this->client = new Client([
            'cert' => $this->certificatePath,
            'ssl_key' => $this->privateKeyPath,
            'verify' => $this->environment === 'production',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Register software with a specific bank (ASPSP)
     *
     * @param string $bankRegistrationEndpoint
     * @return array|null
     */
    public function registerWithBank(string $bankRegistrationEndpoint): ?array
    {
        try {
            $response = $this->client->post($bankRegistrationEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/jwt',
                ],
                'body' => $this->ssa,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Successfully registered with bank', [
                'endpoint' => $bankRegistrationEndpoint,
                'client_id' => $data['client_id'] ?? null,
            ]);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Failed to register with bank', [
                'endpoint' => $bankRegistrationEndpoint,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return null;
        }
    }

    /**
     * Get authorization URL for user consent
     *
     * @param string $bankAuthEndpoint
     * @param string $bankClientId
     * @param array $scopes
     * @param string $state
     * @return string
     */
    public function getAuthorizationUrl(
        string $bankAuthEndpoint,
        string $bankClientId,
        array $scopes = ['accounts'],
        string $state = null
    ): string {
        $state = $state ?? bin2hex(random_bytes(16));

        $params = http_build_query([
            'client_id' => $bankClientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'response_type' => 'code id_token',
            'response_mode' => 'fragment',
            'state' => $state,
            'nonce' => bin2hex(random_bytes(16)),
        ]);

        return $bankAuthEndpoint . '?' . $params;
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $bankTokenEndpoint
     * @param string $code
     * @param string $bankClientId
     * @return array|null
     */
    public function exchangeCodeForToken(
        string $bankTokenEndpoint,
        string $code,
        string $bankClientId
    ): ?array {
        try {
            $response = $this->client->post($bankTokenEndpoint, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $bankClientId,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Successfully exchanged code for token', [
                'bank_client_id' => $bankClientId,
            ]);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Failed to exchange code for token', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return null;
        }
    }

    /**
     * Get list of accounts
     *
     * @param string $bankApiBase
     * @param string $accessToken
     * @return array|null
     */
    public function getAccounts(string $bankApiBase, string $accessToken): ?array
    {
        try {
            $response = $this->client->get($bankApiBase . '/aisp/v3.1/accounts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'x-fapi-financial-id' => $this->getFinancialId($bankApiBase),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Successfully fetched accounts', [
                'count' => count($data['Data']['Account'] ?? []),
            ]);

            return $data['Data']['Account'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch accounts', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return null;
        }
    }

    /**
     * Get transactions for a specific account
     *
     * @param string $bankApiBase
     * @param string $accessToken
     * @param string $accountId
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array|null
     */
    public function getTransactions(
        string $bankApiBase,
        string $accessToken,
        string $accountId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): ?array {
        try {
            $query = [];
            if ($fromDate) $query['fromBookingDateTime'] = $fromDate;
            if ($toDate) $query['toBookingDateTime'] = $toDate;

            $response = $this->client->get(
                $bankApiBase . "/aisp/v3.1/accounts/{$accountId}/transactions",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'x-fapi-financial-id' => $this->getFinancialId($bankApiBase),
                    ],
                    'query' => $query,
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Successfully fetched transactions', [
                'account_id' => $accountId,
                'count' => count($data['Data']['Transaction'] ?? []),
            ]);

            return $data['Data']['Transaction'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch transactions', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return null;
        }
    }

    /**
     * Get account balances
     *
     * @param string $bankApiBase
     * @param string $accessToken
     * @param string $accountId
     * @return array|null
     */
    public function getBalances(
        string $bankApiBase,
        string $accessToken,
        string $accountId
    ): ?array {
        try {
            $response = $this->client->get(
                $bankApiBase . "/aisp/v3.1/accounts/{$accountId}/balances",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'x-fapi-financial-id' => $this->getFinancialId($bankApiBase),
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data['Data']['Balance'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch balances', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Refresh access token using refresh token
     *
     * @param string $bankTokenEndpoint
     * @param string $refreshToken
     * @param string $bankClientId
     * @return array|null
     */
    public function refreshAccessToken(
        string $bankTokenEndpoint,
        string $refreshToken,
        string $bankClientId
    ): ?array {
        try {
            $response = $this->client->post($bankTokenEndpoint, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $bankClientId,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Successfully refreshed access token');

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Failed to refresh access token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract financial ID from bank API base URL
     * This is a placeholder - actual implementation depends on bank
     *
     * @param string $bankApiBase
     * @return string
     */
    private function getFinancialId(string $bankApiBase): string
    {
        // For sandbox testing, return a test financial ID
        // In production, this would be bank-specific
        return 'test-financial-id';
    }

    /**
     * Get list of supported banks for sandbox
     *
     * @return array
     */
    public function getSandboxBanks(): array
    {
        return [
            [
                'id' => 'obie-model-bank',
                'name' => 'Model Bank (Sandbox)',
                'logo' => 'https://www.openbanking.org.uk/wp-content/uploads/2021/09/OB-logo-600x315.png',
                'registration_endpoint' => 'https://matls-sso.openbankingtest.org.uk/register',
                'auth_endpoint' => 'https://matls-sso.openbankingtest.org.uk/oauth2/authorize',
                'token_endpoint' => 'https://matls-sso.openbankingtest.org.uk/oauth2/token',
                'api_base' => 'https://matls-api.openbankingtest.org.uk/open-banking',
                'supported_apis' => ['accounts', 'payments', 'funds-confirmations'],
            ],
        ];
    }

    /**
     * Get list of production banks
     *
     * @return array
     */
    public function getProductionBanks(): array
    {
        return [
            [
                'id' => 'tide',
                'name' => 'Tide',
                'logo' => 'https://assets.tide.co/images/logo-tide.svg',
                'registration_endpoint' => 'https://openbanking.tide.co/register',
                'auth_endpoint' => 'https://openbanking.tide.co/authorize',
                'token_endpoint' => 'https://openbanking.tide.co/token',
                'api_base' => 'https://openbanking.tide.co',
                'financial_id' => 'tide-bank',
                'supported_apis' => ['accounts', 'payments', 'funds-confirmations'],
            ],
            // Add more UK banks here as needed:
            // NatWest, Barclays, Lloyds, HSBC, Monzo, Starling, Revolut, etc.
        ];
    }

    /**
     * Get all available banks based on environment
     *
     * @return array
     */
    public function getAvailableBanks(): array
    {
        return $this->environment === 'sandbox' 
            ? $this->getSandboxBanks() 
            : $this->getProductionBanks();
    }
}
