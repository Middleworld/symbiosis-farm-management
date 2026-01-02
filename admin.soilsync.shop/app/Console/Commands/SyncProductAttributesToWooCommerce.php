<?php

namespace App\Console\Commands;

use App\Models\ProductAttribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProductAttributesToWooCommerce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-attributes:sync-to-woocommerce {--force : Force sync even if woo_id exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Laravel product attributes to WooCommerce attribute taxonomies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing product attributes to WooCommerce...');
        
        $attributes = ProductAttribute::where('is_active', true)->get();
        
        if ($attributes->isEmpty()) {
            $this->warn('No active attributes found in Laravel database.');
            return 0;
        }
        
        $synced = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($attributes as $attribute) {
            try {
                // Skip if already synced unless --force
                if ($attribute->woo_id && !$this->option('force')) {
                    $this->line("⏭️  Skipped: {$attribute->name} (already synced, woo_id: {$attribute->woo_id})");
                    $skipped++;
                    continue;
                }
                
                // Create or update in WooCommerce
                $this->syncAttribute($attribute);
                
                $this->info("✅ Synced: {$attribute->name} (woo_id: {$attribute->woo_id})");
                $synced++;
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to sync {$attribute->name}: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   ✅ Synced: {$synced}");
        $this->info("   ⏭️  Skipped: {$skipped}");
        $this->info("   ❌ Errors: {$errors}");
        
        return 0;
    }
    
    private function syncAttribute($attribute)
    {
        // Check if attribute already exists in WooCommerce
        if ($attribute->woo_id) {
            $existing = DB::connection('wordpress')->select(
                'SELECT attribute_id FROM demo_wp_woocommerce_attribute_taxonomies WHERE attribute_id = ?',
                [$attribute->woo_id]
            );
            
            if (count($existing) > 0) {
                // Update existing
                DB::connection('wordpress')->update(
                    'UPDATE demo_wp_woocommerce_attribute_taxonomies SET attribute_label = ?, attribute_name = ?, attribute_type = ? WHERE attribute_id = ?',
                    [$attribute->name, $attribute->slug, $attribute->type, $attribute->woo_id]
                );
                $this->line("   Updated existing WooCommerce attribute");
            } else {
                // woo_id set but record doesn't exist - create new
                $this->createAttribute($attribute);
            }
        } else {
            // No woo_id - create new
            $this->createAttribute($attribute);
        }
        
        // Sync attribute terms/options
        if ($attribute->is_taxonomy && !empty($attribute->options)) {
            $this->syncAttributeTerms($attribute);
        }
    }
    
    private function createAttribute($attribute)
    {
        // Create in WooCommerce attribute taxonomies table
        DB::connection('wordpress')->insert(
            'INSERT INTO demo_wp_woocommerce_attribute_taxonomies (attribute_name, attribute_label, attribute_type, attribute_orderby, attribute_public) VALUES (?, ?, ?, ?, ?)',
            [$attribute->slug, $attribute->name, $attribute->type, 'menu_order', 0]
        );
        
        // Get the inserted attribute ID
        $attributeId = DB::connection('wordpress')->select('SELECT LAST_INSERT_ID() as id')[0]->id;
        
        // Update Laravel record
        $attribute->update(['woo_id' => $attributeId]);
        
        $this->line("   Created new WooCommerce attribute (ID: {$attributeId})");
    }
    
    private function syncAttributeTerms($attribute)
    {
        $taxonomy = 'pa_' . $attribute->slug;
        $created = 0;
        $existing = 0;
        
        foreach ($attribute->options as $option) {
            $slug = \Str::slug(strtolower($option));
            
            // Check if term exists
            $existingTerm = DB::connection('wordpress')->select(
                'SELECT t.term_id FROM demo_wp_terms t JOIN demo_wp_term_taxonomy tt ON t.term_id = tt.term_id WHERE tt.taxonomy = ? AND t.slug = ?',
                [$taxonomy, $slug]
            );
            
            if (count($existingTerm) === 0) {
                // Create new term
                DB::connection('wordpress')->insert(
                    'INSERT INTO demo_wp_terms (name, slug, term_group) VALUES (?, ?, 0)',
                    [$option, $slug]
                );
                
                $termId = DB::connection('wordpress')->select('SELECT LAST_INSERT_ID() as id')[0]->id;
                
                // Create term taxonomy entry
                DB::connection('wordpress')->insert(
                    'INSERT INTO demo_wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (?, ?, ?, 0, 0)',
                    [$termId, $taxonomy, '']
                );
                
                $created++;
            } else {
                $existing++;
            }
        }
        
        if ($created > 0) {
            $this->line("   Created {$created} terms for {$attribute->name}");
        }
        if ($existing > 0) {
            $this->line("   {$existing} terms already exist");
        }
    }
}
