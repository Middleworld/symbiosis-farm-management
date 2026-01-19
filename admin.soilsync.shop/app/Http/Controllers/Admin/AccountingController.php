<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QuickBooksService;
use App\Services\XeroService;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountingController extends Controller
{
    protected $quickbooks;
    protected $xero;

    public function __construct(QuickBooksService $quickbooks, XeroService $xero)
    {
        $this->quickbooks = $quickbooks;
        $this->xero = $xero;
    }

    // ===== QuickBooks Methods =====

    /**
     * Redirect to QuickBooks authorization
     */
    public function quickbooksAuthorize()
    {
        try {
            $authUrl = $this->quickbooks->getAuthorizationUrl();
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('QuickBooks authorization error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.settings')->with('error', 'Failed to initiate QuickBooks authorization: ' . $e->getMessage());
        }
    }

    /**
     * Handle QuickBooks OAuth callback
     */
    public function quickbooksCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');

            if (!$code) {
                return redirect()->route('admin.settings')->with('error', 'QuickBooks authorization failed - no code received');
            }

            // Verify state for CSRF protection
            if ($state !== csrf_token()) {
                return redirect()->route('admin.settings')->with('error', 'QuickBooks authorization failed - invalid state');
            }

            $tokenData = $this->quickbooks->getAccessToken($code);

            if ($tokenData) {
                return redirect()->route('admin.settings')->with('success', 'QuickBooks connected successfully!');
            } else {
                return redirect()->route('admin.settings')->with('error', 'Failed to obtain QuickBooks access token');
            }
        } catch (\Exception $e) {
            Log::error('QuickBooks callback error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.settings')->with('error', 'QuickBooks authorization failed: ' . $e->getMessage());
        }
    }

    /**
     * Test QuickBooks connection
     */
    public function testQuickBooksConnection()
    {
        try {
            $result = $this->quickbooks->testConnection();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('QuickBooks connection test error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync data to QuickBooks
     */
    public function syncQuickBooks(Request $request)
    {
        try {
            // Get recent transactions that haven't been synced
            $transactions = BankTransaction::where('synced_to_quickbooks', false)
                ->where('transaction_date', '>=', now()->subDays(30))
                ->limit(100)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'date' => $transaction->transaction_date->format('Y-m-d'),
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'category' => $transaction->category ?? 'Uncategorized'
                    ];
                })
                ->toArray();

            if (empty($transactions)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No transactions to sync'
                ]);
            }

            $result = $this->quickbooks->syncTransactions($transactions);

            // Mark transactions as synced
            if ($result['success'] > 0) {
                BankTransaction::where('synced_to_quickbooks', false)
                    ->where('transaction_date', '>=', now()->subDays(30))
                    ->limit($result['success'])
                    ->update(['synced_to_quickbooks' => true]);
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$result['success']} transactions successfully, {$result['failed']} failed",
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('QuickBooks sync error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== Xero Methods =====

    /**
     * Redirect to Xero authorization
     */
    public function xeroAuthorize()
    {
        try {
            $authUrl = $this->xero->getAuthorizationUrl();
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Xero authorization error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.settings')->with('error', 'Failed to initiate Xero authorization: ' . $e->getMessage());
        }
    }

    /**
     * Handle Xero OAuth callback
     */
    public function xeroCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');

            if (!$code) {
                return redirect()->route('admin.settings')->with('error', 'Xero authorization failed - no code received');
            }

            // Verify state for CSRF protection
            if ($state !== csrf_token()) {
                return redirect()->route('admin.settings')->with('error', 'Xero authorization failed - invalid state');
            }

            $tokenData = $this->xero->getAccessToken($code);

            if ($tokenData) {
                return redirect()->route('admin.settings')->with('success', 'Xero connected successfully!');
            } else {
                return redirect()->route('admin.settings')->with('error', 'Failed to obtain Xero access token');
            }
        } catch (\Exception $e) {
            Log::error('Xero callback error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.settings')->with('error', 'Xero authorization failed: ' . $e->getMessage());
        }
    }

    /**
     * Test Xero connection
     */
    public function testXeroConnection()
    {
        try {
            $result = $this->xero->testConnection();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Xero connection test error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync data to Xero
     */
    public function syncXero(Request $request)
    {
        try {
            // Get recent transactions that haven't been synced
            $transactions = BankTransaction::where('synced_to_xero', false)
                ->where('transaction_date', '>=', now()->subDays(30))
                ->limit(100)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'date' => $transaction->transaction_date->format('Y-m-d'),
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'category' => $transaction->category ?? 'Uncategorized',
                        'contact' => $transaction->contact_name ?? 'Farm Transaction'
                    ];
                })
                ->toArray();

            if (empty($transactions)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No transactions to sync'
                ]);
            }

            $result = $this->xero->syncTransactions($transactions);

            // Mark transactions as synced
            if ($result['success'] > 0) {
                BankTransaction::where('synced_to_xero', false)
                    ->where('transaction_date', '>=', now()->subDays(30))
                    ->limit($result['success'])
                    ->update(['synced_to_xero' => true]);
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$result['success']} transactions successfully, {$result['failed']} failed",
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Xero sync error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== Sage Methods =====

    /**
     * Redirect to Sage authorization
     */
    public function sageAuthorize()
    {
        // Sage uses different OAuth flow - typically API key based
        return redirect()->route('admin.settings')->with('error', 'Sage integration requires API key configuration. Please enter your Sage credentials in the settings above.');
    }

    /**
     * Handle Sage OAuth callback (if applicable)
     */
    public function sageCallback(Request $request)
    {
        // Sage typically doesn't use OAuth callback like QuickBooks/Xero
        return redirect()->route('admin.settings')->with('error', 'Sage callback not applicable for API key authentication.');
    }

    /**
     * Test Sage connection
     */
    public function testSageConnection()
    {
        try {
            // TODO: Implement Sage service
            return response()->json([
                'success' => false,
                'error' => 'Sage integration coming soon'
            ]);
        } catch (\Exception $e) {
            Log::error('Sage connection test error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync data to Sage
     */
    public function syncSage(Request $request)
    {
        try {
            // Get recent transactions that haven't been synced
            $transactions = BankTransaction::where('synced_to_sage', false)
                ->where('transaction_date', '>=', now()->subDays(30))
                ->limit(100)
                ->get();

            if ($transactions->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No transactions to sync'
                ]);
            }

            // TODO: Implement Sage sync
            return response()->json([
                'success' => false,
                'message' => 'Sage sync coming soon - ' . $transactions->count() . ' transactions ready'
            ]);
        } catch (\Exception $e) {
            Log::error('Sage sync error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== MYOB Methods =====

    /**
     * Redirect to MYOB authorization
     */
    public function myobAuthorize()
    {
        try {
            // TODO: Implement MYOB OAuth flow
            return redirect()->route('admin.settings')->with('error', 'MYOB integration coming soon.');
        } catch (\Exception $e) {
            Log::error('MYOB authorization error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.settings')->with('error', 'Failed to initiate MYOB authorization: ' . $e->getMessage());
        }
    }

    /**
     * Handle MYOB OAuth callback
     */
    public function myobCallback(Request $request)
    {
        try {
            // TODO: Implement MYOB OAuth callback
            return redirect()->route('admin.settings')->with('error', 'MYOB integration coming soon.');
        } catch (\Exception $e) {
            Log::error('MYOB callback error', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.settings')->with('error', 'MYOB authorization failed: ' . $e->getMessage());
        }
    }

    /**
     * Test MYOB connection
     */
    public function testMyobConnection()
    {
        try {
            // TODO: Implement MYOB service
            return response()->json([
                'success' => false,
                'error' => 'MYOB integration coming soon'
            ]);
        } catch (\Exception $e) {
            Log::error('MYOB connection test error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync data to MYOB
     */
    public function syncMyob(Request $request)
    {
        try {
            // Get recent transactions that haven't been synced
            $transactions = BankTransaction::where('synced_to_myob', false)
                ->where('transaction_date', '>=', now()->subDays(30))
                ->limit(100)
                ->get();

            if ($transactions->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No transactions to sync'
                ]);
            }

            // TODO: Implement MYOB sync
            return response()->json([
                'success' => false,
                'message' => 'MYOB sync coming soon - ' . $transactions->count() . ' transactions ready'
            ]);
        } catch (\Exception $e) {
            Log::error('MYOB sync error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}