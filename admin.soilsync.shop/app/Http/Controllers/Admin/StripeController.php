<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display Stripe payments dashboard
     */
    public function index(Request $request)
    {
        $days = $request->get('days', 30);
        $statistics = $this->stripeService->getPaymentStatistics($days);
        
        // Get payments with pagination support
        $result = $this->stripeService->getPayments([
            'limit' => 25,
            'days' => $days
        ]);
        
        $recentPayments = $result['data'];
        $hasMore = $result['has_more'];
        $lastId = $result['last_id'];
        
        $subscriptions = $this->stripeService->getSubscriptions(5);

        return view('admin.stripe.dashboard', compact(
            'statistics', 
            'recentPayments', 
            'subscriptions',
            'hasMore',
            'lastId',
            'days'
        ));
    }

    /**
     * Get recent payments (AJAX) with pagination
     */
    public function getPayments(Request $request): JsonResponse
    {
        $options = [
            'limit' => $request->get('limit', 25),
        ];
        
        // Support custom date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $options['start_date'] = $request->get('start_date');
            $options['end_date'] = $request->get('end_date');
        } else {
            $options['days'] = $request->get('days', 30);
        }
        
        // Support pagination
        if ($request->has('starting_after')) {
            $options['starting_after'] = $request->get('starting_after');
        }
        
        $result = $this->stripeService->getPayments($options);
        
        return response()->json([
            'success' => true,
            'payments' => $result['data'],
            'has_more' => $result['has_more'],
            'last_id' => $result['last_id'],
            'total' => $result['data']->count()
        ]);
    }

    /**
     * Get payment statistics (AJAX)
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $statistics = $this->stripeService->getPaymentStatistics($days);
        
        return response()->json([
            'success' => true,
            'statistics' => $statistics
        ]);
    }

    /**
     * Search customers
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $query = $request->get('query');
        
        if (!$query) {
            return response()->json(['success' => false, 'message' => 'Query required']);
        }
        
        $customers = $this->stripeService->searchCustomer($query);
        
        return response()->json([
            'success' => true,
            'customers' => $customers
        ]);
    }

    /**
     * Get subscriptions
     */
    public function getSubscriptions(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 25);
        $subscriptions = $this->stripeService->getSubscriptions($limit);
        
        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions
        ]);
    }

    /**
     * Get Stripe account balance (AJAX)
     */
    public function getBalance(): JsonResponse
    {
        $balance = $this->stripeService->getAccountBalance();
        
        return response()->json([
            'success' => true,
            'balance' => $balance
        ]);
    }

    /**
     * Get Stripe payouts (AJAX)
     */
    public function getPayouts(Request $request): JsonResponse
    {
        $options = [
            'limit' => $request->get('limit', 25),
        ];
        
        // Support pagination
        if ($request->has('starting_after')) {
            $options['starting_after'] = $request->get('starting_after');
        }
        
        $result = $this->stripeService->getPayouts($options);
        
        return response()->json([
            'success' => true,
            'payouts' => $result['data'],
            'has_more' => $result['has_more'],
            'last_id' => $result['last_id'],
            'total_amount' => $result['total_amount']
        ]);
    }
}

