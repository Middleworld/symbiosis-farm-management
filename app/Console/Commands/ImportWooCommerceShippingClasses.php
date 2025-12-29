<?php

namespace App\Console\Commands;

use App\Models\ShippingClass;
use App\Services\WooCommerceApiService;
use Illuminate\Console\Command;

class ImportWooCommerceShippingClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woocommerce:import-shipping-classes {--force : Force update existing shipping classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import shipping classes from WooCommerce';

    protected $wooCommerceApi;

    public function __construct(WooCommerceApiService $wooCommerceApi)
    {
        parent::__construct();
        $this->wooCommerceApi = $wooCommerceApi;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching shipping classes and methods from WooCommerce...');

        // First get shipping classes
        $classesResponse = $this->wooCommerceApi->getShippingClasses();
        if (!$classesResponse['success']) {
            $this->error('Failed to fetch shipping classes: ' . $classesResponse['message']);
            return 1;
        }

        $wooShippingClasses = $classesResponse['data'];
        $this->info("Found " . count($wooShippingClasses) . " shipping classes in WooCommerce");

        // Get shipping methods from zone 3 (the zone from the URL)
        $methodsResponse = $this->wooCommerceApi->getShippingMethods(3);
        if (!$methodsResponse['success']) {
            $this->error('Failed to fetch shipping methods: ' . $methodsResponse['message']);
            return 1;
        }

        $shippingMethods = $methodsResponse['data'];
        $this->info("Found " . count($shippingMethods) . " shipping methods in zone 3");

        // Extract pricing from shipping methods
        $classCosts = $this->extractClassCosts($shippingMethods);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($wooShippingClasses as $wooClass) {
            $existingClass = ShippingClass::where('name', $wooClass->name)->first();

            if ($existingClass && !$this->option('force')) {
                $this->warn("Shipping class '{$wooClass->name}' already exists. Use --force to update.");
                $skipped++;
                continue;
            }

            $cost = $classCosts[$wooClass->id] ?? $this->getShippingClassCost($wooClass->name);
            
            $data = [
                'name' => $wooClass->name,
                'slug' => $wooClass->slug ?? \Str::slug($wooClass->name),
                'description' => $wooClass->description ?? null,
                'cost' => $cost,
                'is_free' => $cost == 0.00,
                'is_farm_collection' => false, // Default to paid delivery
                'delivery_zones' => [], // Will be empty by default
                'sort_order' => $wooClass->count ?? 0,
                'is_active' => true,
            ];

            if ($existingClass) {
                $existingClass->update($data);
                $this->info("Updated shipping class: {$wooClass->name}");
                $updated++;
            } else {
                ShippingClass::create($data);
                $this->info("Imported shipping class: {$wooClass->name}");
                $imported++;
            }
        }

        $this->info("Import completed:");
        $this->info("- Imported: {$imported}");
        $this->info("- Updated: {$updated}");
        $this->info("- Skipped: {$skipped}");

        return 0;
    }

    /**
     * Determine if a shipping class should be free based on its name
     */
    private function isShippingClassFree(string $name): bool
    {
        $freeClasses = [
            'Additional Item',
            'Annual Charge',
            'Annual Charge - Fortnightly Delivery',
        ];

        return in_array($name, $freeClasses);
    }

    /**
     * Extract shipping class costs from shipping methods
     */
    private function extractClassCosts(array $shippingMethods): array
    {
        $classCosts = [];

        foreach ($shippingMethods as $method) {
            if (isset($method->settings)) {
                foreach ($method->settings as $key => $setting) {
                    if (str_starts_with($key, 'class_cost_') && isset($setting->value)) {
                        $classId = str_replace('class_cost_', '', $key);
                        $cost = (float) $setting->value;
                        
                        // Take the maximum cost across all shipping methods
                        if (!isset($classCosts[$classId]) || $cost > $classCosts[$classId]) {
                            $classCosts[$classId] = $cost;
                        }
                    }
                }
            }
        }

        return $classCosts;
    }

    /**
     * Get the cost for a shipping class based on its name
     * Pricing as specified by the user - shipping classes don't include pricing in WooCommerce API
     */
    private function getShippingClassCost(string $name): float
    {
        $costMap = [
            'Additional Item' => 0.00,
            'Annual Charge' => 0.00,
            'Annual Charge - Fortnightly Delivery' => 0.00,
            'Fortnighly Charge' => 7.50,
            'Monthly Charge' => 15.00,
            'Monthly Charge - Fortnightly Delivery' => 22.50,
            'Weekly Charge' => 4.00,
        ];

        return $costMap[$name] ?? 0.00;
    }
}
