<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TideService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TideController extends Controller
{
    protected $tideService;

    public function __construct(TideService $tideService)
    {
        $this->tideService = $tideService;
    }

    /**
     * Display Tide bank dashboard
     */
    public function index()
    {
        $accountSummary = $this->tideService->getAccountSummary();
        $financialSummary = $this->tideService->getFinancialSummary(30);
        $businessProfile = $this->tideService->getBusinessProfile();
        $monthlyBreakdown = $this->tideService->getMonthlyBreakdown(12);
        $expenseBreakdown = $this->tideService->getExpenseBreakdown(12);

        return view('admin.tide.dashboard', compact(
            'accountSummary',
            'financialSummary',
            'businessProfile',
            'monthlyBreakdown',
            'expenseBreakdown'
        ));
    }

    /**
     * Get account transactions (AJAX)
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $accountId = $request->get('account_id');
        $limit = $request->get('limit', 50);
        $days = $request->get('days', 30);

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required'
            ], 400);
        }

        $transactions = $this->tideService->getTransactions($accountId, $limit, $days);

        return response()->json([
            'success' => true,
            'transactions' => $transactions ? ($transactions['transactions'] ?? []) : [],
            'account_id' => $accountId
        ]);
    }

    /**
     * Get account balance (AJAX)
     */
    public function getBalance(Request $request): JsonResponse
    {
        $accountId = $request->get('account_id');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required'
            ], 400);
        }

        $balance = $this->tideService->getAccountBalance($accountId);

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'account_id' => $accountId
        ]);
    }

    /**
     * Get financial summary (AJAX)
     */
    public function getFinancialSummary(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $summary = $this->tideService->getFinancialSummary($days);

        return response()->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    /**
     * Refresh account data
     */
    public function refresh(Request $request): JsonResponse
    {
        // Clear cache to force fresh data
        \Illuminate\Support\Facades\Cache::forget('tide_accounts');
        \Illuminate\Support\Facades\Cache::forget('tide_business_profile');

        // Clear account-specific caches
        $accounts = $this->tideService->getAccounts();
        if ($accounts && isset($accounts['accounts'])) {
            foreach ($accounts['accounts'] as $account) {
                $accountId = $account['id'];
                \Illuminate\Support\Facades\Cache::forget("tide_balance_{$accountId}");
                // Note: We don't clear transaction caches as they have specific parameters
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Account data refreshed successfully'
        ]);
    }
}