<?php

namespace App\Console\Commands;

use App\Models\ProductAttribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProductAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attributes:create-essential {--force : Force creation even if attributes already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create essential product attributes for subscription products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating essential product attributes...');

        // Define the attributes to create
        $attributes = [
            [
                'name' => 'Payment option',
                'slug' => 'payment-option',
                'type' => 'select',
                'options' => ['Weekly', 'Monthly', 'Annual'],
                'is_visible' => true,
                'is_variation' => true,
                'is_taxonomy' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Frequency',
                'slug' => 'frequency',
                'type' => 'select',
                'options' => ['Weekly', 'Fortnightly'],
                'is_visible' => true,
                'is_variation' => true,
                'is_taxonomy' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($attributes as $attributeData) {
            $this->createAttribute($attributeData);
        }

        $this->info('Essential product attributes created successfully!');
    }

    /**
     * Create a single product attribute
     */
    private function createAttribute(array $data)
    {
        // Check if attribute already exists
        $existing = ProductAttribute::where('slug', $data['slug'])->first();

        if ($existing && !$this->option('force')) {
            $this->warn("Attribute '{$data['name']}' already exists. Use --force to recreate.");
            return;
        }

        if ($existing && $this->option('force')) {
            $this->info("Recreating attribute '{$data['name']}'...");
            $existing->delete();
        }

        try {
            $attribute = ProductAttribute::create($data);

            // Sync to WooCommerce
            $this->syncAttributeToWooCommerce($attribute);

            $this->info("✓ Created and synced '{$data['name']}' attribute (WooCommerce ID: {$attribute->woo_id})");

        } catch (\Exception $e) {
            $this->error("✗ Failed to create '{$data['name']}' attribute: " . $e->getMessage());
        }
    }

    /**
     * Sync attribute to WooCommerce database
     */
    private function syncAttributeToWooCommerce($attribute)
    {
        try {
            if ($attribute->woo_id) {
                // Update existing
                $existing = DB::connection('wordpress')->select(
                    'SELECT t.term_id FROM demo_wp_terms t JOIN demo_wp_term_taxonomy tt ON t.term_id = tt.term_id WHERE tt.taxonomy = ? AND t.term_id = ?',
                    ['pa_' . $attribute->slug, $attribute->woo_id]
                );

                if (count($existing) > 0) {
                    // Update existing
                    DB::connection('wordpress')->update(
                        'UPDATE demo_wp_terms SET name = ?, slug = ? WHERE term_id = ?',
                        [$attribute->name, $attribute->slug, $attribute->woo_id]
                    );
                } else {
                    // WooCommerce record missing, recreate
                    $this->createAttributeInDatabase($attribute);
                }
            } else {
                // Create new
                $this->createAttributeInDatabase($attribute);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync product attribute to WooCommerce: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create attribute directly in WooCommerce database
     */
    private function createAttributeInDatabase($attribute)
    {
        // Insert into WordPress terms
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_terms (name, slug, term_group) VALUES (?, ?, 0)',
            [$attribute->name, $attribute->slug]
        );

        $termId = DB::connection('wordpress')->getPdo()->lastInsertId();

        // Insert into term_taxonomy
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (?, ?, ?, 0, 0)',
            [$termId, 'pa_' . $attribute->slug, '']
        );

        // Update the attribute with WooCommerce ID
        $attribute->update(['woo_id' => $termId]);

        // Create terms for options if they exist
        if ($attribute->options && is_array($attribute->options)) {
            foreach ($attribute->options as $option) {
                $optionSlug = Str::slug($option);

                // Check if option term exists
                $existingOption = DB::connection('wordpress')->select(
                    'SELECT t.term_id FROM demo_wp_terms t JOIN demo_wp_term_taxonomy tt ON t.term_id = tt.term_id WHERE tt.taxonomy = ? AND t.slug = ?',
                    ['pa_' . $attribute->slug, $optionSlug]
                );

                if (count($existingOption) == 0) {
                    // Create option term
                    DB::connection('wordpress')->insert(
                        'INSERT INTO demo_wp_terms (name, slug, term_group) VALUES (?, ?, 0)',
                        [$option, $optionSlug]
                    );

                    $optionTermId = DB::connection('wordpress')->getPdo()->lastInsertId();

                    DB::connection('wordpress')->insert(
                        'INSERT INTO demo_wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (?, ?, ?, ?, 0)',
                        [$optionTermId, 'pa_' . $attribute->slug, '', $termId]
                    );
                }
            }
        }
    }
}
