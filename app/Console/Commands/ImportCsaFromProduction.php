<?php

namespace App\Console\Commands;

use App\Models\CsaSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportCsaFromProduction extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'csa:import-production 
                            {--dry-run : Show what would be imported without making changes}
                            {--active-only : Only import active subscriptions}
                            {--clear : Clear existing imported subscriptions before import}';

    /**
     * The console command description.
     */
    protected $description = 'Import WooCommerce subscriptions from production WordPress into CSA subscriptions table';

    /**
     * Production WordPress database credentials
     */
    private string $prodDbHost = 'localhost';
    private string $prodDbName = 'wp_pxmxy';
    private string $prodDbUser = 'wp_pteke';
    private string $prodDbPass = '4_Sl8a0kcaTgr*El';
    private string $prodDbPrefix = 'D6sPMX_';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $activeOnly = $this->option('active-only');
        $clear = $this->option('clear');

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║     Production WooCommerce → CSA Subscription Import         ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Connect to production WordPress database
        $this->info('');
        $this->info('📡 Connecting to production WordPress database...');
        
        try {
            $pdo = new \PDO(
                "mysql:host={$this->prodDbHost};dbname={$this->prodDbName};charset=utf8mb4",
                $this->prodDbUser,
                $this->prodDbPass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $this->info('   ✅ Connected to production database');
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to connect: ' . $e->getMessage());
            return 1;
        }

        // Clear existing imported subscriptions if requested
        if ($clear && !$dryRun) {
            $deleted = CsaSubscription::where('imported_from_woo', true)->delete();
            $this->warn("   🗑️  Cleared {$deleted} previously imported subscriptions");
        }

        // Fetch subscriptions from production
        $statusFilter = $activeOnly ? "AND p.post_status = 'wc-active'" : "";
        
        $query = "
            SELECT 
                p.ID as woo_subscription_id,
                p.post_status,
                p.post_date as created_at,
                MAX(CASE WHEN pm.meta_key = '_customer_user' THEN pm.meta_value END) as customer_id,
                MAX(CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END) as first_name,
                MAX(CASE WHEN pm.meta_key = '_billing_last_name' THEN pm.meta_value END) as last_name,
                MAX(CASE WHEN pm.meta_key = '_billing_email' THEN pm.meta_value END) as email,
                MAX(CASE WHEN pm.meta_key = '_billing_phone' THEN pm.meta_value END) as phone,
                MAX(CASE WHEN pm.meta_key = '_order_total' THEN pm.meta_value END) as total,
                MAX(CASE WHEN pm.meta_key = '_billing_interval' THEN pm.meta_value END) as billing_interval,
                MAX(CASE WHEN pm.meta_key = '_billing_period' THEN pm.meta_value END) as billing_period,
                MAX(CASE WHEN pm.meta_key = '_shipping_first_name' THEN pm.meta_value END) as shipping_first_name,
                MAX(CASE WHEN pm.meta_key = '_shipping_last_name' THEN pm.meta_value END) as shipping_last_name,
                MAX(CASE WHEN pm.meta_key = '_shipping_address_1' THEN pm.meta_value END) as address_1,
                MAX(CASE WHEN pm.meta_key = '_shipping_address_2' THEN pm.meta_value END) as address_2,
                MAX(CASE WHEN pm.meta_key = '_shipping_city' THEN pm.meta_value END) as city,
                MAX(CASE WHEN pm.meta_key = '_shipping_postcode' THEN pm.meta_value END) as postcode,
                MAX(CASE WHEN pm.meta_key = '_order_shipping' THEN pm.meta_value END) as shipping_total,
                MAX(CASE WHEN pm.meta_key = 'customer_week_type' THEN pm.meta_value END) as week_type,
                MAX(CASE WHEN pm.meta_key = '_schedule_next_payment' THEN pm.meta_value END) as next_payment,
                MAX(CASE WHEN pm.meta_key = '_schedule_start' THEN pm.meta_value END) as start_date,
                MAX(CASE WHEN pm.meta_key = '_schedule_end' THEN pm.meta_value END) as end_date
            FROM {$this->prodDbPrefix}posts p
            LEFT JOIN {$this->prodDbPrefix}postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_subscription'
            {$statusFilter}
            GROUP BY p.ID, p.post_status, p.post_date
            ORDER BY p.ID DESC
        ";

        $subscriptions = $pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $this->info("   📦 Found " . count($subscriptions) . " subscriptions");

        // Fetch line items for each subscription
        $this->info('');
        $this->info('📋 Processing subscriptions...');
        $this->info('');
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($subscriptions as $sub) {
            // Get line items for this subscription
            $lineItemQuery = "
                SELECT 
                    oi.order_item_name as product_name,
                    MAX(CASE WHEN oim.meta_key = '_line_total' THEN oim.meta_value END) as line_total,
                    MAX(CASE WHEN oim.meta_key = '_product_id' THEN oim.meta_value END) as product_id
                FROM {$this->prodDbPrefix}woocommerce_order_items oi
                LEFT JOIN {$this->prodDbPrefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
                WHERE oi.order_item_type = 'line_item'
                AND oi.order_id = :subscription_id
                GROUP BY oi.order_item_id, oi.order_item_name
                LIMIT 1
            ";
            
            $stmt = $pdo->prepare($lineItemQuery);
            $stmt->execute(['subscription_id' => $sub['woo_subscription_id']]);
            $lineItem = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Check if already imported
            $existing = CsaSubscription::where('woo_subscription_id', $sub['woo_subscription_id'])->first();
            if ($existing) {
                $skipped++;
                continue;
            }

            // Map WooCommerce data to CSA subscription
            $csaData = $this->mapToCsaSubscription($sub, $lineItem);

            // Display row
            $customerName = trim(($sub['first_name'] ?? '') . ' ' . ($sub['last_name'] ?? ''));
            $boxSize = $csaData['box_size'];
            $price = '£' . number_format($csaData['price'], 2);
            $billing = $csaData['payment_schedule'];
            $status = $this->mapStatus($sub['post_status']);

            $this->line(sprintf(
                "   %s #%d | %-20s | %-15s | %8s | %-10s | %s",
                $dryRun ? '👁️' : '✅',
                $sub['woo_subscription_id'],
                substr($customerName, 0, 20),
                substr($boxSize, 0, 15),
                $price,
                $billing,
                $status
            ));

            if (!$dryRun) {
                try {
                    CsaSubscription::create($csaData);
                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("   ❌ Error importing #{$sub['woo_subscription_id']}: " . $e->getMessage());
                    Log::error('CSA Import Error', [
                        'subscription_id' => $sub['woo_subscription_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $imported++;
            }
        }

        // Summary
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║     Import Summary                                           ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info("   ✅ Imported: {$imported}");
        $this->info("   ⏭️  Skipped (already exists): {$skipped}");
        if ($errors > 0) {
            $this->error("   ❌ Errors: {$errors}");
        }

        if ($dryRun) {
            $this->warn('');
            $this->warn('   ℹ️  This was a DRY RUN. Run without --dry-run to actually import.');
        }

        return 0;
    }

    /**
     * Map WooCommerce subscription data to CSA subscription format
     */
    private function mapToCsaSubscription(array $sub, ?array $lineItem): array
    {
        $customerName = trim(($sub['first_name'] ?? '') . ' ' . ($sub['last_name'] ?? ''));
        $productName = $lineItem['product_name'] ?? 'Unknown Box';
        
        // Determine box size from product name
        $boxSize = $this->extractBoxSize($productName);
        
        // Determine payment schedule from billing period
        $paymentSchedule = $this->mapPaymentSchedule($sub['billing_period'], $sub['billing_interval']);
        
        // Determine delivery frequency from week_type
        $deliveryFrequency = $this->mapDeliveryFrequency($sub['week_type']);
        
        // Determine fulfillment type (delivery vs collection)
        $fulfillmentType = $this->determineFulfillmentType($sub);
        
        // Build address
        $address = $this->buildAddress($sub);
        
        // Map status
        $status = $this->mapStatus($sub['post_status']);

        return [
            'customer_id' => $sub['customer_id'] ?? 0,
            'customer_email' => $sub['email'] ?? '',
            'customer_name' => $customerName,
            'product_id' => $lineItem['product_id'] ?? null,
            'woo_subscription_id' => $sub['woo_subscription_id'],
            'woo_product_id' => $lineItem['product_id'] ?? null,
            'payment_schedule' => $paymentSchedule,
            'delivery_frequency' => $deliveryFrequency,
            'box_size' => $boxSize,
            'fulfillment_type' => $fulfillmentType,
            'price' => (float) ($sub['total'] ?? 0),
            'season_total' => $this->calculateSeasonTotal((float) ($sub['total'] ?? 0), $paymentSchedule),
            'delivery_address' => $address,
            'delivery_postcode' => $sub['postcode'] ?? '',
            'delivery_day' => 'Thursday', // Default - would need custom meta
            'delivery_time' => null,
            'fortnightly_week' => $this->mapFortnightlyWeek($sub['week_type']),
            'season_start_date' => $sub['start_date'] ? date('Y-m-d', strtotime($sub['start_date'])) : now()->format('Y-m-d'),
            'season_end_date' => $sub['end_date'] ? date('Y-m-d', strtotime($sub['end_date'])) : now()->addMonths(6)->format('Y-m-d'),
            'next_billing_date' => $sub['next_payment'] ? date('Y-m-d', strtotime($sub['next_payment'])) : null,
            'next_delivery_date' => $this->calculateNextDelivery($sub['week_type']),
            'deliveries_remaining' => $this->estimateDeliveriesRemaining($sub, $deliveryFrequency),
            'status' => $status,
            'is_paused' => $status === 'on-hold',
            'imported_from_woo' => true,
            'metadata' => json_encode([
                'woo_product_name' => $productName,
                'woo_billing_period' => $sub['billing_period'],
                'woo_billing_interval' => $sub['billing_interval'],
                'imported_at' => now()->toIso8601String(),
            ]),
        ];
    }

    /**
     * Extract box size from product name
     */
    private function extractBoxSize(string $productName): string
    {
        $productName = strtolower($productName);
        
        if (str_contains($productName, 'single')) {
            return 'Single';
        } elseif (str_contains($productName, 'couple')) {
            return "Couple's";
        } elseif (str_contains($productName, 'small family')) {
            return 'Small Family';
        } elseif (str_contains($productName, 'large family')) {
            return 'Large Family';
        } elseif (str_contains($productName, 'family')) {
            return 'Family';
        }
        
        return 'Standard';
    }

    /**
     * Map WooCommerce billing period to payment schedule
     */
    private function mapPaymentSchedule(?string $period, ?string $interval): string
    {
        $period = strtolower($period ?? 'month');
        $interval = (int) ($interval ?? 1);

        if ($period === 'year') {
            return 'Annually';
        } elseif ($period === 'month') {
            return 'Monthly';
        } elseif ($period === 'week') {
            return $interval >= 2 ? 'Fortnightly' : 'Weekly';
        }

        return 'Monthly';
    }

    /**
     * Map week type to delivery frequency
     */
    private function mapDeliveryFrequency(?string $weekType): string
    {
        if ($weekType === 'A' || $weekType === 'B') {
            return 'Fortnightly';
        }
        return 'Weekly';
    }

    /**
     * Map week type to fortnightly week
     */
    private function mapFortnightlyWeek(?string $weekType): ?string
    {
        if ($weekType === 'A' || $weekType === 'B') {
            return $weekType;
        }
        return null;
    }

    /**
     * Determine fulfillment type from shipping
     */
    private function determineFulfillmentType(array $sub): string
    {
        $shippingTotal = (float) ($sub['shipping_total'] ?? 0);
        
        // If shipping is £0, it's collection
        if ($shippingTotal <= 0) {
            return 'Collection';
        }
        
        return 'Delivery';
    }

    /**
     * Build full address string
     */
    private function buildAddress(array $sub): string
    {
        $parts = array_filter([
            $sub['address_1'] ?? '',
            $sub['address_2'] ?? '',
            $sub['city'] ?? '',
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Map WooCommerce status to CSA status
     */
    private function mapStatus(string $wooStatus): string
    {
        return match ($wooStatus) {
            'wc-active' => 'active',
            'wc-on-hold' => 'on-hold',
            'wc-cancelled' => 'cancelled',
            'wc-pending' => 'pending',
            'wc-expired' => 'cancelled',
            default => 'active',
        };
    }

    /**
     * Calculate season total based on payment schedule
     */
    private function calculateSeasonTotal(float $price, string $paymentSchedule): float
    {
        return match ($paymentSchedule) {
            'Weekly' => $price * 26, // 6 months
            'Fortnightly' => $price * 13,
            'Monthly' => $price * 6,
            'Annually' => $price,
            default => $price * 6,
        };
    }

    /**
     * Calculate next delivery date
     */
    private function calculateNextDelivery(?string $weekType): string
    {
        $now = now();
        $thursday = $now->copy()->next('Thursday');
        
        // For fortnightly, check if this week matches
        if ($weekType === 'A' || $weekType === 'B') {
            $currentWeek = $now->weekOfYear;
            $isWeekA = $currentWeek % 2 === 1;
            
            if (($weekType === 'A' && !$isWeekA) || ($weekType === 'B' && $isWeekA)) {
                $thursday->addWeek();
            }
        }
        
        return $thursday->format('Y-m-d');
    }

    /**
     * Estimate remaining deliveries
     */
    private function estimateDeliveriesRemaining(array $sub, string $frequency): int
    {
        $endDate = $sub['end_date'] ? strtotime($sub['end_date']) : strtotime('+6 months');
        $now = time();
        $weeksRemaining = max(0, ($endDate - $now) / (7 * 24 * 60 * 60));
        
        return $frequency === 'Fortnightly' 
            ? (int) ceil($weeksRemaining / 2) 
            : (int) ceil($weeksRemaining);
    }
}
