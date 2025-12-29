<?php

namespace App\Services;

use App\Models\CsaSubscription;
use Illuminate\Support\Collection;

class CsaSubscriptionService
{
    /**
     * Get delivery schedule data from local CSA subscriptions.
     * Replaces WpApiService::getDeliveryScheduleData()
     * 
     * @param int $limit Maximum number of subscriptions to return
     * @return Collection
     */
    public function getDeliveryScheduleData(int $limit = 100): Collection
    {
        $subscriptions = CsaSubscription::with(['deliveries', 'product', 'variation'])
            ->whereIn('status', ['active', 'pending', 'on-hold'])
            ->orderBy('next_delivery_date', 'asc')
            ->limit($limit)
            ->get();

        return $subscriptions->map(function ($subscription) {
            return $this->formatForDeliverySchedule($subscription);
        });
    }

    /**
     * Format subscription data to match the structure expected by DeliveryController.
     * Maintains compatibility with existing view logic.
     */
    public function formatForDeliverySchedule(CsaSubscription $subscription): array
    {
        // Split customer name into first/last
        $nameParts = explode(' ', $subscription->customer_name, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';
        
        // Format in WooCommerce-compatible structure for DeliveryController.transformScheduleData
        return [
            'id' => $subscription->id,
            'woo_subscription_id' => $subscription->woo_subscription_id,
            'customer_id' => $subscription->customer_id,
            'customer_email' => $subscription->customer_email,
            'customer_name' => $subscription->customer_name,
            'status' => $subscription->status,
            'date_created' => $subscription->created_at->toIso8601String(),
            'date_modified' => $subscription->updated_at->toIso8601String(),
            'total' => (string) $subscription->price,
            
            // WooCommerce-compatible fields for frequency detection
            'billing_period' => 'week',
            'billing_interval' => $subscription->delivery_frequency === 'Fortnightly' ? 2 : 1,
            'next_payment_date' => $subscription->next_billing_date?->format('Y-m-d'),
            
            // Billing address (required by controller)
            'billing' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $subscription->customer_email,
                'phone' => '', // TODO: Add phone field to subscriptions table
            ],
            
            // Shipping total determines delivery vs collection in transformScheduleData
            'shipping_total' => $subscription->fulfillment_type === 'Delivery' ? '5.00' : '0.00',
            'shipping' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'address_1' => $subscription->delivery_address ?? '',
                'address_2' => '',
                'city' => '',
                'state' => '',
                'postcode' => $subscription->delivery_postcode ?? '',
            ],
            
            // Line items with meta_data for attributes
            'line_items' => [
                [
                    'name' => $subscription->product->name ?? 'Veg Box',
                    'quantity' => 1,
                    'total' => (string) $subscription->price,
                    'meta_data' => [
                        ['key' => 'frequency', 'value' => $subscription->delivery_frequency],
                        ['key' => 'box_size', 'value' => $subscription->box_size],
                        ['key' => 'payment_schedule', 'value' => $subscription->payment_schedule],
                    ]
                ]
            ],
            
            // Meta data for subscription-level info
            'meta_data' => [
                ['key' => '_delivery_day', 'value' => $subscription->delivery_day],
                ['key' => '_delivery_time', 'value' => $subscription->delivery_time],
                ['key' => '_fortnightly_week', 'value' => $subscription->fortnightly_week],
                ['key' => '_fulfillment_type', 'value' => $subscription->fulfillment_type],
                ['key' => 'customer_week_type', 'value' => $subscription->fortnightly_week ?? 'Weekly'],
            ],
            
            // Subscription details
            'payment_schedule' => $subscription->payment_schedule,
            'delivery_frequency' => $subscription->delivery_frequency,
            'box_size' => $subscription->box_size,
            'fulfillment_type' => $subscription->fulfillment_type,
            'price' => (float) $subscription->price,
            'season_total' => (float) $subscription->season_total,
            
            // Delivery information
            'delivery_address' => $subscription->delivery_address,
            'delivery_postcode' => $subscription->delivery_postcode,
            'delivery_day' => $subscription->delivery_day,
            'delivery_time' => $subscription->delivery_time,
            'fortnightly_week' => $subscription->fortnightly_week,
            
            // Dates
            'season_start_date' => $subscription->season_start_date?->format('Y-m-d'),
            'season_end_date' => $subscription->season_end_date?->format('Y-m-d'),
            'next_billing_date' => $subscription->next_billing_date?->format('Y-m-d'),
            'next_delivery_date' => $subscription->next_delivery_date?->format('Y-m-d'),
            'deliveries_remaining' => $subscription->deliveries_remaining,
            
            // Status tracking
            'is_paused' => $subscription->is_paused,
            'paused_until' => $subscription->paused_until?->format('Y-m-d'),
            'skipped_dates' => $subscription->skipped_dates ?? [],
            
            // Product information
            'product_name' => $subscription->product?->name ?? 'Unknown Product',
            'variation_name' => $subscription->variation?->name ?? null,
            
            // Source tracking
            'source' => 'laravel', // Distinguish from WooCommerce API data
            'imported_from_woo' => $subscription->imported_from_woo,
            
            // Additional metadata
            'metadata' => $subscription->metadata ?? [],
        ];
    }

    /**
     * Get subscriptions by delivery date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return CsaSubscription::active()
            ->whereBetween('next_delivery_date', [$startDate, $endDate])
            ->with(['deliveries', 'product'])
            ->get();
    }

    /**
     * Get subscriptions by week number.
     */
    public function getByWeekNumber(int $weekNumber, int $year = null): Collection
    {
        $year = $year ?? now()->year;
        
        // Calculate start and end of week
        $startDate = now()->setISODate($year, $weekNumber)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        
        return $this->getByDateRange(
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    /**
     * Get subscriptions by fulfillment type (Delivery or Collection).
     */
    public function getByFulfillmentType(string $type): Collection
    {
        return CsaSubscription::active()
            ->where('fulfillment_type', $type)
            ->with(['deliveries', 'product'])
            ->get();
    }

    /**
     * Get deliveries (not collections).
     */
    public function getDeliveries(): Collection
    {
        return $this->getByFulfillmentType('Delivery');
    }

    /**
     * Get collections (not deliveries).
     */
    public function getCollections(): Collection
    {
        return $this->getByFulfillmentType('Collection');
    }

    /**
     * Get subscriptions by delivery day.
     */
    public function getByDeliveryDay(string $day): Collection
    {
        return CsaSubscription::active()
            ->where('delivery_day', $day)
            ->with(['deliveries', 'product'])
            ->get();
    }

    /**
     * Get fortnightly subscriptions by week type (A or B).
     */
    public function getByFortnightlyWeek(string $week): Collection
    {
        return CsaSubscription::active()
            ->fortnightly()
            ->where('fortnightly_week', $week)
            ->with(['deliveries', 'product'])
            ->get();
    }

    /**
     * Get subscription statistics for dashboard.
     */
    public function getStatistics(): array
    {
        $total = CsaSubscription::count();
        $active = CsaSubscription::active()->count();
        $paused = CsaSubscription::where('is_paused', true)->count();
        $deliveries = CsaSubscription::deliveries()->active()->count();
        $collections = CsaSubscription::collections()->active()->count();
        
        return [
            'total' => $total,
            'active' => $active,
            'paused' => $paused,
            'deliveries' => $deliveries,
            'collections' => $collections,
            'weekly' => CsaSubscription::weekly()->active()->count(),
            'fortnightly' => CsaSubscription::fortnightly()->active()->count(),
        ];
    }

    /**
     * Test connection to local database.
     */
    public function testConnection(): array
    {
        try {
            $count = CsaSubscription::count();
            return [
                'success' => true,
                'message' => 'Connected to local Laravel database',
                'subscription_count' => $count,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
