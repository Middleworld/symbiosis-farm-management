<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TideService
{
    protected $apiKey;
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.tide.api_key');
        $this->clientId = config('services.tide.client_id');
        $this->clientSecret = config('services.tide.client_secret');
        $this->baseUrl = config('services.tide.base_url');
    }

    /**
     * Get access token for Tide API
     */
    protected function getAccessToken()
    {
        $cacheKey = 'tide_access_token';

        return Cache::remember($cacheKey, 3500, function () { // Cache for 58 minutes (tokens typically last 1 hour)
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post("{$this->baseUrl}/oauth2/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['access_token'];
                }

                Log::error('Tide API token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Tide API token request exception', [
                    'message' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Make authenticated request to Tide API
     */
    protected function makeRequest($method, $endpoint, $params = [])
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->{$method}("{$this->baseUrl}/v1{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Tide API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Tide API request exception', [
                'method' => $method,
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get business accounts
     */
    public function getAccounts()
    {
        return Cache::remember('tide_accounts', 300, function () { // Cache for 5 minutes
            return $this->makeRequest('get', '/accounts');
        });
    }

    /**
     * Get account balance
     */
    public function getAccountBalance($accountId)
    {
        return Cache::remember("tide_balance_{$accountId}", 300, function () use ($accountId) {
            return $this->makeRequest('get', "/accounts/{$accountId}/balance");
        });
    }

    /**
     * Get account transactions
     */
    public function getTransactions($accountId, $limit = 50, $days = 30)
    {
        $cacheKey = "tide_transactions_{$accountId}_{$limit}_{$days}";

        return Cache::remember($cacheKey, 300, function () use ($accountId, $limit, $days) {
            $params = [
                'limit' => $limit,
                'from_date' => now()->subDays($days)->format('Y-m-d'),
                'to_date' => now()->format('Y-m-d'),
            ];

            return $this->makeRequest('get', "/accounts/{$accountId}/transactions", $params);
        });
    }

    /**
     * Get business profile
     */
    public function getBusinessProfile()
    {
        return Cache::remember('tide_business_profile', 3600, function () { // Cache for 1 hour
            return $this->makeRequest('get', '/business');
        });
    }

    /**
     * Get account summary for dashboard
     */
    public function getAccountSummary()
    {
        $accounts = $this->getAccounts();

        if (!$accounts || !isset($accounts['accounts'])) {
            return [];
        }

        $summary = [];
        foreach ($accounts['accounts'] as $account) {
            $accountId = $account['id'];
            $balance = $this->getAccountBalance($accountId);
            $transactions = $this->getTransactions($accountId, 10, 7); // Last 7 days, 10 transactions

            $summary[] = [
                'id' => $accountId,
                'name' => $account['name'] ?? 'Unknown Account',
                'type' => $account['type'] ?? 'business',
                'currency' => $account['currency'] ?? 'GBP',
                'balance' => $balance ? $balance['balance'] : 0,
                'available_balance' => $balance ? ($balance['available_balance'] ?? $balance['balance']) : 0,
                'recent_transactions' => $transactions ? ($transactions['transactions'] ?? []) : [],
            ];
        }

        return $summary;
    }

    /**
     * Get financial summary for reporting
     */
    public function getFinancialSummary($days = 30)
    {
        $accounts = $this->getAccounts();

        if (!$accounts || !isset($accounts['accounts'])) {
            return null;
        }

        $totalBalance = 0;
        $totalIncome = 0;
        $totalExpenses = 0;
        $transactions = [];

        foreach ($accounts['accounts'] as $account) {
            $accountId = $account['id'];
            $balance = $this->getAccountBalance($accountId);
            $accountTransactions = $this->getTransactions($accountId, 100, $days);

            if ($balance) {
                $totalBalance += $balance['balance'];
            }

            if ($accountTransactions && isset($accountTransactions['transactions'])) {
                foreach ($accountTransactions['transactions'] as $transaction) {
                    $amount = $transaction['amount'];
                    $transactions[] = $transaction;

                    if ($amount > 0) {
                        $totalIncome += $amount;
                    } else {
                        $totalExpenses += abs($amount);
                    }
                }
            }
        }

        return [
            'total_balance' => $totalBalance,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_flow' => $totalIncome - $totalExpenses,
            'transaction_count' => count($transactions),
            'period_days' => $days,
        ];
    }

    /**
     * Get monthly breakdown of income and expenditure
     */
    public function getMonthlyBreakdown($months = 12)
    {
        $accounts = $this->getAccounts();

        if (!$accounts || !isset($accounts['accounts'])) {
            return null;
        }

        $monthlyData = [];
        
        // Initialize last N months
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthlyData[$key] = [
                'month' => $date->format('M Y'),
                'year_month' => $key,
                'income' => 0,
                'expenses' => 0,
                'transactions' => [],
            ];
        }

        // Fetch transactions for each account
        foreach ($accounts['accounts'] as $account) {
            $accountId = $account['id'];
            $transactions = $this->getTransactions($accountId, 1000, $months * 31);

            if ($transactions && isset($transactions['transactions'])) {
                foreach ($transactions['transactions'] as $transaction) {
                    $amount = $transaction['amount'];
                    $date = isset($transaction['date']) ? $transaction['date'] : (isset($transaction['created_at']) ? $transaction['created_at'] : null);
                    
                    if (!$date) continue;

                    $monthKey = date('Y-m', strtotime($date));

                    if (isset($monthlyData[$monthKey])) {
                        $monthlyData[$monthKey]['transactions'][] = $transaction;
                        
                        if ($amount > 0) {
                            $monthlyData[$monthKey]['income'] += $amount;
                        } else {
                            $monthlyData[$monthKey]['expenses'] += abs($amount);
                        }
                    }
                }
            }
        }

        // Calculate net for each month
        foreach ($monthlyData as $key => $data) {
            $monthlyData[$key]['net'] = $data['income'] - $data['expenses'];
            $monthlyData[$key]['transaction_count'] = count($data['transactions']);
        }

        return array_values($monthlyData);
    }

    /**
     * Get expense breakdown by category
     */
    public function getExpenseBreakdown($months = 12)
    {
        $accounts = $this->getAccounts();

        if (!$accounts || !isset($accounts['accounts'])) {
            return null;
        }

        $categories = [];

        // Fetch all transactions
        foreach ($accounts['accounts'] as $account) {
            $accountId = $account['id'];
            $transactions = $this->getTransactions($accountId, 1000, $months * 31);

            if ($transactions && isset($transactions['transactions'])) {
                foreach ($transactions['transactions'] as $transaction) {
                    $amount = $transaction['amount'];
                    
                    // Only process expenses (negative amounts)
                    if ($amount >= 0) continue;

                    $category = $transaction['category'] ?? $transaction['merchant_category'] ?? 'Uncategorized';
                    $description = $transaction['description'] ?? '';

                    // Auto-categorize based on description if no category
                    if ($category === 'Uncategorized' || empty($category)) {
                        $category = $this->categorizeTransaction($description);
                    }

                    if (!isset($categories[$category])) {
                        $categories[$category] = [
                            'category' => $category,
                            'total' => 0,
                            'count' => 0,
                            'transactions' => [],
                        ];
                    }

                    $categories[$category]['total'] += abs($amount);
                    $categories[$category]['count']++;
                    $categories[$category]['transactions'][] = $transaction;
                }
            }
        }

        // Sort by total amount descending
        usort($categories, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $categories;
    }

    /**
     * Auto-categorize transactions based on description
     */
    protected function categorizeTransaction($description)
    {
        $description = strtolower($description);

        $categories = [
            'Utilities' => ['electric', 'water', 'gas', 'utility', 'british gas', 'scottish power'],
            'Supplies' => ['seed', 'compost', 'supplies', 'tools', 'equipment'],
            'Insurance' => ['insurance'],
            'Professional Services' => ['accountant', 'legal', 'solicitor', 'consultant'],
            'Marketing' => ['advertising', 'marketing', 'facebook', 'google ads'],
            'Software' => ['subscription', 'software', 'saas', 'hosting'],
            'Wages' => ['salary', 'wages', 'payroll', 'paye'],
            'Rent' => ['rent', 'lease'],
            'Transport' => ['fuel', 'petrol', 'diesel', 'vehicle', 'mot', 'insurance'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($description, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'Other';
    }
}