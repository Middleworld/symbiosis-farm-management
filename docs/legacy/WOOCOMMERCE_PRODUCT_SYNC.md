# WooCommerce Product Sync - Complete Guide

## Overview
The Laravel admin system now fully syncs products with WooCommerce, including:
- **Simple & Variable products** with automatic type detection
- **Product variations** with attributes, pricing, and stock management
- **Solidarity Pricing** (pay-what-you-can) meta data sync
- **Automated sync** on product save via Eloquent events
- **Manual sync** via Artisan command with dry-run capability

## Architecture

### Models
- **`Product`**: Main product model with `product_type` field ('simple' or 'variable')
- **`ProductVariation`**: Variations with auto-sync trigger on save
- **Fields**:
  - `woo_product_id` (Product): Links to WooCommerce product ID
  - `woo_variation_id` (ProductVariation): Links to WooCommerce variation ID
  - `metadata` (Product): JSON field storing solidarity pricing settings

### Services
**`WooCommerceApiService`** (`app/Services/WooCommerceApiService.php`):
- `syncProduct(Product $product)` - Main sync method
- `syncProductVariations(Product $product)` - Syncs all variations
- `syncSolidarityPricingMeta(Product $product)` - Syncs solidarity pricing to WordPress meta
- `formatProductForWooCommerce()` - Converts Laravel data to WooCommerce format

### Automatic Sync Triggers
**ProductVariation Model** has a `saved` event:
```php
static::saved(function ($variation) {
    // Automatically triggers parent product sync when variation changes
    $wooService->syncProduct($variation->product);
});
```

## Solidarity Pricing Integration

### Laravel Admin UI
Products have a "💚 Solidarity Pricing" card with:
- Checkbox to enable/disable
- Min/Recommended/Max price fields (auto-calculate if left blank)
- Stored in `metadata` JSON column

### WordPress/WooCommerce Sync
Solidarity pricing data syncs to WordPress `postmeta` table:
```
_mwf_solidarity_pricing = 'yes'
_mwf_min_price = '8.40'
_mwf_recommended_price = '12.00'
_mwf_max_price = '20.00'
```

### WordPress Plugin
**MWF Solidarity Pricing Plugin** (`wp-content/plugins/mwf-solidarity-pricing/`):
- Checks `_mwf_solidarity_pricing` meta to enable slider
- Reads custom prices or auto-calculates from recommended
- Adds price slider to WooCommerce product pages
- Handles custom pricing in cart and checkout

## Usage

### Via Admin Interface
1. **Create/Edit Product**:
   - Go to Products → Edit
   - Select product type (Simple/Variable)
   - Enable "💚 Solidarity Pricing" if desired
   - Set pricing options
   - **Save** → Automatically syncs to WooCommerce

2. **Managing Variations** (Variable Products):
   - Click "Add Variation" button
   - Set attributes (Size, Color, etc.), price, stock
   - **Save** → Auto-syncs variation to WooCommerce
   - WooCommerce ID displayed in variations table

### Via Artisan Command

**Sync All Products**:
```bash
php artisan products:sync-to-woocommerce
```

**Sync Specific Product**:
```bash
php artisan products:sync-to-woocommerce --product=123
```

**Force Sync (Re-sync Already Synced Products)**:
```bash
php artisan products:sync-to-woocommerce --force
```

**Dry Run (Preview Without Changes)**:
```bash
php artisan products:sync-to-woocommerce --dry-run
```

## Data Flow

### Simple Product Sync
```
Laravel Product (ID: 5)
  ├─ name: "Small Veg Box"
  ├─ sku: "VB-SMALL"
  ├─ price: 12.00
  ├─ product_type: "simple"
  └─ metadata: {
      solidarity_pricing_enabled: true,
      solidarity_min_price: "8.40",
      solidarity_recommended_price: "12.00",
      solidarity_max_price: "20.00"
    }
       ↓
[WooCommerceApiService::syncProduct()]
       ↓
WooCommerce Product (ID: 456)
  ├─ type: "simple"
  ├─ regular_price: "12.00"
  ├─ manage_stock: true
  └─ postmeta:
      ├─ _mwf_solidarity_pricing: "yes"
      ├─ _mwf_min_price: "8.40"
      ├─ _mwf_recommended_price: "12.00"
      └─ _mwf_max_price: "20.00"
       ↓
[MWF Solidarity Pricing Plugin]
       ↓
Customer sees price slider (£8.40 - £20.00)
```

### Variable Product Sync
```
Laravel Product (ID: 10, product_type: "variable")
  └─ variations:
      ├─ ProductVariation (ID: 1)
      │   ├─ sku: "VB-SMALL-WK"
      │   ├─ price: 12.00
      │   └─ attributes: {size: "Small"}
      └─ ProductVariation (ID: 2)
          ├─ sku: "VB-LARGE-WK"
          ├─ price: 18.00
          └─ attributes: {size: "Large"}
       ↓
[WooCommerceApiService::syncProduct() + syncProductVariations()]
       ↓
WooCommerce Product (ID: 789, type: "variable")
  └─ variations:
      ├─ Variation (ID: 111)
      │   ├─ regular_price: "12.00"
      │   └─ attributes: [{name: "size", option: "Small"}]
      └─ Variation (ID: 112)
          ├─ regular_price: "18.00"
          └─ attributes: [{name: "size", option: "Large"}]
```

## Database Schema

### Products Table
```sql
ALTER TABLE products ADD COLUMN product_type ENUM('simple', 'variable') DEFAULT 'simple';
ALTER TABLE products ADD INDEX idx_product_type (product_type);
```

### Product Variations Table
```sql
ALTER TABLE product_variations ADD COLUMN woo_variation_id BIGINT UNSIGNED NULL;
ALTER TABLE product_variations ADD INDEX idx_woo_variation_id (woo_variation_id);
```

## Troubleshooting

### Product Not Syncing
**Check**:
1. Is product active? (`is_active = 1`)
2. Does product have required fields? (name, SKU, price for simple)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Run sync with dry-run to preview: `php artisan products:sync-to-woocommerce --dry-run`

### Variations Not Appearing in WooCommerce
**Check**:
1. Parent product must be type "variable"
2. Variations need valid SKU, price, and attributes
3. Check `woo_variation_id` column - should populate after sync
4. Force re-sync: `php artisan products:sync-to-woocommerce --product=<id> --force`

### Solidarity Pricing Not Showing
**Check**:
1. Meta box enabled in product edit screen?
2. Metadata saved correctly? Check `products.metadata` JSON
3. WooCommerce meta synced? Query: 
   ```sql
   SELECT * FROM wp_postmeta WHERE post_id=<woo_product_id> AND meta_key LIKE '_mwf%';
   ```
4. MWF plugin active? Check WordPress plugins page
5. Product meta box rendering? Check `render_price_slider()` method

### Manual Meta Sync
If solidarity pricing meta didn't sync:
```php
use App\Services\WooCommerceApiService;
use App\Models\Product;

$product = Product::find(<id>);
$wooService = app(WooCommerceApiService::class);
$wooService->syncSolidarityPricingMeta($product);
```

## Related Files

### Laravel Files
- `app/Models/Product.php` - Main product model
- `app/Models/ProductVariation.php` - Variation model with auto-sync event
- `app/Services/WooCommerceApiService.php` - Sync service
- `app/Console/Commands/SyncProductsToWooCommerce.php` - CLI sync command
- `resources/views/admin/products/edit.blade.php` - Product form with solidarity pricing UI
- `database/migrations/2025_12_21_032100_add_product_type_to_products_table.php`
- `database/migrations/2025_12_21_033832_add_woo_variation_id_to_product_variations_table.php`

### WordPress Files
- `wp-content/plugins/mwf-solidarity-pricing/mwf-solidarity-pricing.php` - Main plugin
- `wp-content/plugins/mwf-solidarity-pricing/assets/js/price-slider.js` - Slider JS
- `wp-content/plugins/mwf-solidarity-pricing/assets/css/price-slider.css` - Slider CSS

## Future Enhancements
- [ ] Scheduled sync job (cron)
- [ ] Webhook from WooCommerce for stock updates
- [ ] Bulk sync with progress bar
- [ ] Sync product images
- [ ] Sync product categories from WooCommerce taxonomy
- [ ] Two-way sync (WooCommerce → Laravel)
