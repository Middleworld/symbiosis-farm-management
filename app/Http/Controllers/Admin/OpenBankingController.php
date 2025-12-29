<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OpenBankingService;
use App\Models\OpenBankingConnection;
use App\Models\OpenBankingAccount;
use App\Models\OpenBankingTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OpenBankingController extends Controller
{
    private OpenBankingService $openBanking;

    public function __construct(OpenBankingService $openBanking)
    {
        $this->openBanking = $openBanking;
    }

    /**
     * Show bank connection dashboard
     */
    public function index()
    {
        $connections = OpenBankingConnection::with('accounts')->get();
        $availableBanks = $this->openBanking->getAvailableBanks();

        return view('admin.openbanking.index', compact('connections', 'availableBanks'));
    }

    /**
     * Register with a bank
     */
    public function register(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|string',
        ]);

        $banks = collect($this->openBanking->getAvailableBanks());
        $bank = $banks->firstWhere('id', $request->bank_id);

        if (!$bank) {
            return back()->with('error', 'Bank not found');
        }

        // Check if already registered
        $existing = OpenBankingConnection::where('bank_id', $bank['id'])->first();
        if ($existing) {
            return back()->with('info', 'Already registered with this bank');
        }

        // For sandbox/test banks, skip dynamic registration and use Software Statement client ID
        // Production banks require proper DCR (Dynamic Client Registration)
        if (config('openbanking.environment') === 'sandbox') {
            $connection = OpenBankingConnection::create([
                'bank_id' => $bank['id'],
                'bank_name' => $bank['name'],
                'bank_client_id' => config('openbanking.client_id'), // Use Software Statement ID
                'bank_client_secret' => null,
                'registration_endpoint' => $bank['registration_endpoint'],
                'auth_endpoint' => $bank['auth_endpoint'],
                'token_endpoint' => $bank['token_endpoint'],
                'api_base' => $bank['api_base'],
                'status' => 'registered',
                'metadata' => ['sandbox' => true, 'software_statement_id' => config('openbanking.software_id')],
            ]);
        } else {
            // Production: Use Dynamic Client Registration
            $registration = $this->openBanking->registerWithBank($bank['registration_endpoint']);

            if (!$registration) {
                return back()->with('error', 'Failed to register with bank');
            }

            $connection = OpenBankingConnection::create([
                'bank_id' => $bank['id'],
                'bank_name' => $bank['name'],
                'bank_client_id' => $registration['client_id'],
                'bank_client_secret' => $registration['client_secret'] ?? null,
                'registration_endpoint' => $bank['registration_endpoint'],
                'auth_endpoint' => $bank['auth_endpoint'],
                'token_endpoint' => $bank['token_endpoint'],
                'api_base' => $bank['api_base'],
                'status' => 'registered',
                'metadata' => $registration,
            ]);
        }

        return redirect()
            ->route('admin.openbanking.authorize', $connection->id)
            ->with('success', 'Successfully registered with ' . $bank['name']);
    }

    /**
     * Initiate OAuth2 authorization
     */
    public function authorize(OpenBankingConnection $connection)
    {
        if ($connection->isAuthorized()) {
            return back()->with('info', 'Already authorized with this bank');
        }

        // For sandbox with mTLS requirements, we skip browser-based consent
        // and mark as authorized directly for testing purposes
        if (config('openbanking.environment') === 'sandbox') {
            // In sandbox, we'll create a mock authorization
            // Real banks would require user consent via browser
            $connection->update([
                'status' => 'authorized',
                'access_token' => 'sandbox_access_token_' . bin2hex(random_bytes(16)),
                'refresh_token' => 'sandbox_refresh_token_' . bin2hex(random_bytes(16)),
                'token_expires_at' => now()->addHours(1),
            ]);

            return redirect()
                ->route('admin.openbanking.index')
                ->with('success', 'Sandbox connection authorized. You can now sync accounts.');
        }

        // Production: Standard OAuth2 flow with browser redirect
        $state = bin2hex(random_bytes(16));
        session(['openbanking_state' => $state, 'openbanking_connection_id' => $connection->id]);

        $authUrl = $this->openBanking->getAuthorizationUrl(
            $connection->auth_endpoint,
            $connection->bank_client_id,
            ['accounts'],
            $state
        );

        return redirect($authUrl);
    }

    /**
     * Handle OAuth2 callback
     */
    public function callback(Request $request)
    {
        $state = session('openbanking_state');
        $connectionId = session('openbanking_connection_id');

        if (!$state || !$connectionId || $request->state !== $state) {
            return redirect()
                ->route('admin.openbanking.index')
                ->with('error', 'Invalid authorization state');
        }

        session()->forget(['openbanking_state', 'openbanking_connection_id']);

        $connection = OpenBankingConnection::findOrFail($connectionId);

        // Exchange code for token
        $tokenData = $this->openBanking->exchangeCodeForToken(
            $connection->token_endpoint,
            $request->code,
            $connection->bank_client_id
        );

        if (!$tokenData) {
            return redirect()
                ->route('admin.openbanking.index')
                ->with('error', 'Failed to obtain access token');
        }

        // Update connection with tokens
        $connection->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
            'status' => 'authorized',
        ]);

        // Immediately fetch accounts
        $this->syncAccounts($connection);

        return redirect()
            ->route('admin.openbanking.index')
            ->with('success', 'Successfully connected to ' . $connection->bank_name);
    }

    /**
     * Sync accounts from bank
     */
    public function syncAccounts(OpenBankingConnection $connection)
    {
        if (!$connection->isAuthorized()) {
            return back()->with('error', 'Connection not authorized');
        }

        // For sandbox, create mock accounts instead of calling API
        if (config('openbanking.environment') === 'sandbox') {
            // Delete existing accounts for this connection
            $connection->accounts()->delete();

            // Create mock test accounts
            $mockAccounts = [
                [
                    'account_id' => 'ACC-' . bin2hex(random_bytes(8)),
                    'account_type' => 'Personal',
                    'account_subtype' => 'CurrentAccount',
                    'currency' => 'GBP',
                    'nickname' => 'Business Current Account',
                    'sort_code' => '20-00-00',
                    'account_number' => '12345678',
                    'balance' => 15420.50,
                    'balance_type' => 'InterimAvailable',
                    'balance_updated_at' => now(),
                ],
                [
                    'account_id' => 'ACC-' . bin2hex(random_bytes(8)),
                    'account_type' => 'Personal',
                    'account_subtype' => 'Savings',
                    'currency' => 'GBP',
                    'nickname' => 'Business Savings',
                    'sort_code' => '20-00-00',
                    'account_number' => '87654321',
                    'balance' => 8500.00,
                    'balance_type' => 'InterimAvailable',
                    'balance_updated_at' => now(),
                ],
            ];

            foreach ($mockAccounts as $accountData) {
                $connection->accounts()->create($accountData);
            }

            return back()->with('success', 'Synced ' . count($mockAccounts) . ' sandbox accounts');
        }

        // Production: Call real bank API
        $accounts = $this->openBanking->getAccounts(
            $connection->api_base,
            $connection->access_token
        );

        if (!$accounts) {
            return back()->with('error', 'Failed to fetch accounts');
        }

        foreach ($accounts as $accountData) {
            $account = OpenBankingAccount::updateOrCreate(
                [
                    'connection_id' => $connection->id,
                    'account_id' => $accountData['AccountId'],
                ],
                [
                    'account_type' => $accountData['AccountType'] ?? 'Unknown',
                    'account_subtype' => $accountData['AccountSubType'] ?? null,
                    'currency' => $accountData['Currency'] ?? 'GBP',
                    'nickname' => $accountData['Nickname'] ?? null,
                    'account_number' => $accountData['Account'][0]['Identification'] ?? null,
                    'sort_code' => $accountData['Account'][0]['SecondaryIdentification'] ?? null,
                    'is_active' => true,
                    'metadata' => $accountData,
                ]
            );

            // Fetch balances
            $this->syncBalance($account);
        }

        return redirect()
            ->route('admin.openbanking.index')
            ->with('success', 'Accounts synced successfully');
    }

    /**
     * Sync balance for an account
     */
    private function syncBalance(OpenBankingAccount $account)
    {
        $connection = $account->connection;

        $balances = $this->openBanking->getBalances(
            $connection->api_base,
            $connection->access_token,
            $account->account_id
        );

        if ($balances && count($balances) > 0) {
            $balance = $balances[0];
            
            $account->update([
                'balance' => $balance['Amount']['Amount'],
                'balance_type' => $balance['Type'] ?? null,
                'balance_updated_at' => now(),
            ]);
        }
    }

    /**
     * Sync transactions for an account
     */
    public function syncTransactions(OpenBankingAccount $account, Request $request)
    {
        $connection = $account->connection;

        if (!$connection->isAuthorized()) {
            return back()->with('error', 'Connection not authorized');
        }

        // For sandbox, create mock transactions
        if (config('openbanking.environment') === 'sandbox') {
            // Delete existing transactions for this account
            $account->transactions()->delete();

            // Create realistic mock transactions
            $mockTransactions = [
                [
                    'transaction_id' => 'TXN-' . bin2hex(random_bytes(8)),
                    'type' => 'Credit',
                    'status' => 'Booked',
                    'booking_datetime' => now()->subDays(2),
                    'value_datetime' => now()->subDays(2),
                    'amount' => 2500.00,
                    'currency' => 'GBP',
                    'merchant_name' => 'Customer Payment',
                    'description' => 'Invoice payment for produce delivery',
                    'balance_after' => $account->balance,
                ],
                [
                    'transaction_id' => 'TXN-' . bin2hex(random_bytes(8)),
                    'type' => 'Debit',
                    'status' => 'Booked',
                    'booking_datetime' => now()->subDays(5),
                    'value_datetime' => now()->subDays(5),
                    'amount' => 450.75,
                    'currency' => 'GBP',
                    'merchant_name' => 'Agricultural Supplies Ltd',
                    'merchant_category' => '5261',
                    'description' => 'Seeds and equipment',
                    'balance_after' => $account->balance - 2500.00,
                ],
                [
                    'transaction_id' => 'TXN-' . bin2hex(random_bytes(8)),
                    'type' => 'Debit',
                    'status' => 'Booked',
                    'booking_datetime' => now()->subDays(7),
                    'value_datetime' => now()->subDays(7),
                    'amount' => 125.00,
                    'currency' => 'GBP',
                    'merchant_name' => 'Fuel Station',
                    'merchant_category' => '5541',
                    'description' => 'Vehicle fuel',
                    'balance_after' => $account->balance - 2500.00 + 450.75,
                ],
                [
                    'transaction_id' => 'TXN-' . bin2hex(random_bytes(8)),
                    'type' => 'Credit',
                    'status' => 'Booked',
                    'booking_datetime' => now()->subDays(10),
                    'value_datetime' => now()->subDays(10),
                    'amount' => 3200.00,
                    'currency' => 'GBP',
                    'merchant_name' => 'Farmers Market',
                    'description' => 'Weekly market sales',
                    'balance_after' => $account->balance - 2500.00 + 450.75 + 125.00,
                ],
            ];

            foreach ($mockTransactions as $txnData) {
                $account->transactions()->create($txnData);
            }

            return back()->with('success', 'Synced ' . count($mockTransactions) . ' sandbox transactions');
        }

        // Production: Call real bank API
        $fromDate = $request->from_date ?? now()->subMonth()->toIso8601String();
        $toDate = $request->to_date ?? now()->toIso8601String();

        $transactions = $this->openBanking->getTransactions(
            $connection->api_base,
            $connection->access_token,
            $account->account_id,
            $fromDate,
            $toDate
        );

        if (!$transactions) {
            return back()->with('error', 'Failed to fetch transactions');
        }

        foreach ($transactions as $txnData) {
            OpenBankingTransaction::updateOrCreate(
                [
                    'account_id' => $account->id,
                    'transaction_id' => $txnData['TransactionId'],
                ],
                [
                    'type' => $txnData['CreditDebitIndicator'],
                    'status' => $txnData['Status'],
                    'booking_datetime' => Carbon::parse($txnData['BookingDateTime']),
                    'value_datetime' => isset($txnData['ValueDateTime']) ? Carbon::parse($txnData['ValueDateTime']) : null,
                    'amount' => abs($txnData['Amount']['Amount']),
                    'currency' => $txnData['Amount']['Currency'],
                    'merchant_name' => $txnData['MerchantDetails']['MerchantName'] ?? null,
                    'merchant_category' => $txnData['MerchantDetails']['MerchantCategoryCode'] ?? null,
                    'description' => $txnData['TransactionInformation'] ?? null,
                    'reference' => $txnData['TransactionReference'] ?? null,
                    'balance_after' => $txnData['Balance']['Amount']['Amount'] ?? null,
                    'metadata' => $txnData,
                ]
            );
        }

        return back()->with('success', count($transactions) . ' transactions synced');
    }

    /**
     * Show account details with transactions
     */
    public function showAccount(OpenBankingAccount $account)
    {
        $account->load(['connection', 'transactions' => function ($query) {
            $query->orderBy('booking_datetime', 'desc')->limit(100);
        }]);

        return view('admin.openbanking.account', compact('account'));
    }

    /**
     * Disconnect bank
     */
    public function disconnect(OpenBankingConnection $connection)
    {
        $connection->update(['status' => 'revoked']);
        
        return back()->with('success', 'Disconnected from ' . $connection->bank_name);
    }

    /**
     * Refresh access token
     */
    public function refreshToken(OpenBankingConnection $connection)
    {
        if (!$connection->refresh_token) {
            return back()->with('error', 'No refresh token available');
        }

        $tokenData = $this->openBanking->refreshAccessToken(
            $connection->token_endpoint,
            $connection->refresh_token,
            $connection->bank_client_id
        );

        if (!$tokenData) {
            return back()->with('error', 'Failed to refresh token');
        }

        $connection->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
        ]);

        return back()->with('success', 'Token refreshed successfully');
    }
}
