<?php

namespace App\Console\Commands;

use App\Models\ShippingClass;
use App\Services\WooCommerceApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncShippingClassesToWooCommerce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shipping-classes:sync-to-woocommerce {--force : Force update existing WooCommerce shipping classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync shipping classes from Laravel to WooCommerce';

    protected WooCommerceApiService $wooCommerceApi;

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
        $this->info('Starting shipping classes sync to WooCommerce...');

        $shippingClasses = ShippingClass::all();
        $this->info("Found {$shippingClasses->count()} shipping classes to sync");

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($shippingClasses as $shippingClass) {
            try {
                if ($shippingClass->woo_id && !$this->option('force')) {
                    // Update existing - check if it still exists in WooCommerce
                    $existing = DB::connection('wordpress')->select(
                        'SELECT t.term_id FROM demo_wp_terms t JOIN demo_wp_term_taxonomy tt ON t.term_id = tt.term_id WHERE tt.taxonomy = ? AND t.term_id = ?',
                        ['product_shipping_class', $shippingClass->woo_id]
                    );

                    if (count($existing) > 0) {
                        // Update existing
                        DB::connection('wordpress')->update(
                            'UPDATE demo_wp_terms SET name = ?, slug = ? WHERE term_id = ?',
                            [$shippingClass->name, $shippingClass->slug, $shippingClass->woo_id]
                        );
                        DB::connection('wordpress')->update(
                            'UPDATE demo_wp_term_taxonomy SET description = ? WHERE term_id = ? AND taxonomy = ?',
                            [$shippingClass->description ?: '', $shippingClass->woo_id, 'product_shipping_class']
                        );
                        $updated++;
                        $this->info("Updated: {$shippingClass->name}");
                    } else {
                        // WooCommerce record missing, recreate
                        $this->createShippingClassInDatabase($shippingClass);
                        $created++;
                        $this->info("Recreated: {$shippingClass->name}");
                    }
                } else {
                    // Create new
                    $this->createShippingClassInDatabase($shippingClass);
                    $created++;
                    $this->info("Created: {$shippingClass->name}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Exception for {$shippingClass->name}: {$e->getMessage()}");
            }
        }

        $this->info("Sync completed: {$created} created, {$updated} updated, {$errors} errors");
    }

    /**
     * Create shipping class directly in WooCommerce database
     */
    private function createShippingClassInDatabase($shippingClass)
    {
        // Insert into WordPress terms
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_terms (name, slug, term_group) VALUES (?, ?, 0)',
            [$shippingClass->name, $shippingClass->slug]
        );

        // Get the inserted term ID
        $termId = DB::connection('wordpress')->select('SELECT LAST_INSERT_ID() as id')[0]->id;

        // Insert into term_taxonomy
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (?, ?, ?, 0, 0)',
            [$termId, 'product_shipping_class', $shippingClass->description ?: '']
        );

        // Update Laravel record with WooCommerce ID
        $shippingClass->update(['woo_id' => $termId]);
    }
}
