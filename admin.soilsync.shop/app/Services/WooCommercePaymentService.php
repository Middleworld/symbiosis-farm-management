<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WooCommercePaymentService
{
    protected $wpConnection;
    
    public function __construct()
    {
        // Use WooCommerce database connection
        $this->wpConnection = DB::connection('wordpress');
    }

    /**
     * Get recent payments from WooCommerce orders
     */
    public function getRecentPayments($limit = 25, $days = 30)
    {
        try {
            // Don't add prefix manually - it's already set in the connection config
            
            // Get orders from last X days with payment info
            $orders = $this->wpConnection->table('posts as p')
                ->join('postmeta as pm_total', function($join) {
                    $join->on('p.ID', '=', 'pm_total.post_id')
                         ->where('pm_total.meta_key', '=', '_order_total');
                })
                ->join('postmeta as pm_status', function($join) {
                    $join->on('p.ID', '=', 'pm_status.post_id')
                         ->where('pm_status.meta_key', '=', '_payment_method');
                })
                ->leftJoin('postmeta as pm_customer', function($join) {
                    $join->on('p.ID', '=', 'pm_customer.post_id')
                         ->where('pm_customer.meta_key', '=', '_billing_email');
                })
                ->leftJoin('postmeta as pm_first_name', function($join) {
                    $join->on('p.ID', '=', 'pm_first_name.post_id')
                         ->where('pm_first_name.meta_key', '=', '_billing_first_name');
                })
                ->leftJoin('postmeta as pm_last_name', function($join) {
                    $join->on('p.ID', '=', 'pm_last_name.post_id')
                         ->where('pm_last_name.meta_key', '=', '_billing_last_name');
                })
                ->leftJoin('postmeta as pm_transaction_id', function($join) {
                    $join->on('p.ID', '=', 'pm_transaction_id.post_id')
                         ->where('pm_transaction_id.meta_key', '=', '_transaction_id');
                })
                ->where('p.post_type', 'shop_order')
                ->where('p.post_status', 'like', 'wc-%')
                ->where('pm_status.meta_value', 'stripe')
                ->where('p.post_date', '>=', Carbon::now()->subDays($days))
                ->select([
                    'p.ID as order_id',
                    'p.post_date as created',
                    'p.post_status as order_status',
                    'pm_total.meta_value as amount',
                    'pm_customer.meta_value as customer_email',
                    'pm_first_name.meta_value as first_name',
                    'pm_last_name.meta_value as last_name',
                    'pm_transaction_id.meta_value as transaction_id'
                ])
                ->orderBy('p.post_date', 'desc')
                ->limit($limit)
                ->get();

            return collect($orders)->map(function ($order) {
                return [
                    'id' => $order->transaction_id ?: 'wc_' . $order->order_id,
                    'amount' => (float) $order->amount,
                    'currency' => 'GBP',
                    'status' => $this->mapOrderStatus($order->order_status),
                    'customer_email' => $order->customer_email,
                    'customer_name' => trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')),
                    'description' => 'WooCommerce Order #' . $order->order_id,
                    'created' => Carbon::parse($order->created),
                    'payment_method' => [
                        'type' => 'Card',
                        'brand' => 'Stripe',
                        'last4' => 'via WC'
                    ],
                    'refunded' => false,
                    'amount_refunded' => 0,
                    'receipt_url' => null,
                    'order_id' => $order->order_id,
                    'source' => 'WooCommerce'
                ];
            });

        } catch (\Exception $e) {
            Log::error('WooCommerce Payment Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get payment statistics for dashboard
     */
    public function getPaymentStatistics($days = 30)
    {
        try {
            $payments = $this->getRecentPayments(1000, $days);
            $successfulPayments = $payments->where('status', 'succeeded');

            return [
                'total_revenue' => $successfulPayments->sum('amount'),
                'total_transactions' => $successfulPayments->count(),
                'failed_transactions' => $payments->where('status', 'failed')->count(),
                'refunded_amount' => $payments->sum('amount_refunded'),
                'average_transaction' => $successfulPayments->avg('amount') ?: 0,
                'top_customers' => $this->getTopCustomers($successfulPayments),
                'daily_revenue' => $this->getDailyRevenue($successfulPayments, $days),
            ];

        } catch (\Exception $e) {
            Log::error('WooCommerce Statistics Error: ' . $e->getMessage());
            return [
                'total_revenue' => 0,
                'total_transactions' => 0,
                'failed_transactions' => 0,
                'refunded_amount' => 0,
                'average_transaction' => 0,
                'top_customers' => [],
                'daily_revenue' => [],
            ];
        }
    }

    private function mapOrderStatus($wcStatus)
    {
        $statusMap = [
            'wc-completed' => 'succeeded',
            'wc-processing' => 'succeeded',
            'wc-on-hold' => 'pending',
            'wc-pending' => 'pending',
            'wc-cancelled' => 'failed',
            'wc-refunded' => 'refunded',
            'wc-failed' => 'failed',
        ];

        return $statusMap[$wcStatus] ?? 'pending';
    }

    private function getTopCustomers($payments)
    {
        return $payments->groupBy('customer_email')
            ->map(function ($customerPayments, $email) {
                return [
                    'email' => $email ?: 'Unknown',
                    'total' => $customerPayments->sum('amount'),
                    'count' => $customerPayments->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();
    }

    private function getDailyRevenue($payments, $days)
    {
        $dailyRevenue = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayPayments = $payments->filter(function ($payment) use ($date) {
                return $payment['created']->format('Y-m-d') === $date;
            });

            $dailyRevenue[] = [
                'date' => $date,
                'revenue' => $dayPayments->sum('amount')
            ];
        }

        return $dailyRevenue;
    }
}
