# Solidarity Pricing Global Settings

## Overview
Global solidarity pricing percentage settings allow administrators to configure minimum and maximum price ranges across all products from a central location.

## Configuration Location

**Admin Settings Page:** `/admin/settings` → "External Integrations" section

### Settings Fields

1. **Minimum Price Percentage** (`solidarity_min_percent`)
   - Default: 70%
   - Range: 0-100%
   - Meaning: Customers pay at least X% of recommended price
   - Example: 70% of £10.00 = £7.00 minimum

2. **Maximum Price Percentage** (`solidarity_max_percent`)
   - Default: 167%
   - Range: 100-500%
   - Meaning: Customers can pay up to X% to support others
   - Example: 167% of £10.00 = £16.70 maximum

## Database Storage

Settings are stored in the `settings` table:

```sql
SELECT * FROM settings WHERE key IN ('solidarity_min_percent', 'solidarity_max_percent');
```

**Schema:**
- `key`: Setting identifier (e.g., 'solidarity_min_percent')
- `value`: Integer percentage value (e.g., '70')
- `type`: 'integer'
- `description`: Human-readable explanation

## How It Works

### 1. Admin Sets Global Defaults
Navigate to **Settings** → **External Integrations** → **Solidarity Pricing** card:

```
┌─────────────────────────────────────────┐
│ Solidarity Pricing                      │
├─────────────────────────────────────────┤
│ ℹ️ These percentages control minimum    │
│   and maximum pricing across products   │
│                                         │
│ Minimum Price Percentage: [70] %        │
│ (% of recommended price)                │
│ Default: 70% (customers pay at least...)│
│                                         │
│ Maximum Price Percentage: [167] %       │
│ (% of recommended price)                │
│ Default: 167% (customers can pay up to) │
└─────────────────────────────────────────┘
```

### 2. Product Edit Form Uses Global Settings
When editing a product (`/admin/products/{id}/edit`):

**Solidarity Pricing Card:**
```
✓ Enable Solidarity Pricing

Product Price: £10.00
Recommended Price: £10.00 (auto-filled)

Min Price: [Auto: £7.00]  ← Calculated from 70%
Max Price: [Auto: £16.70] ← Calculated from 167%

💡 Default ranges: 70% of recommended (£7.00) to 167% (£16.70)
   Change defaults in Settings
```

**Dynamic Calculation in PHP:**
```php
@php
    $minPercent = \App\Models\Setting::where('key', 'solidarity_min_percent')->value('value') ?? 70;
    $maxPercent = \App\Models\Setting::where('key', 'solidarity_max_percent')->value('value') ?? 167;
    $calculatedMin = ($product->price ?? 0) * ($minPercent / 100);
    $calculatedMax = ($product->price ?? 0) * ($maxPercent / 100);
@endphp
```

**Dynamic Calculation in JavaScript:**
```javascript
const minPercent = {{ $minPercent ?? 70 }} / 100;
const maxPercent = {{ $maxPercent ?? 167 }} / 100;

function updatePlaceholders(price) {
    minField.placeholder = 'Auto: £' + (price * minPercent).toFixed(2);
    maxField.placeholder = 'Auto: £' + (price * maxPercent).toFixed(2);
}
```

### 3. WooCommerce Sync
When syncing to WooCommerce, the min/max prices are saved as WordPress post metadata:

**Database:**
```
wp_postmeta table:
- _mwf_solidarity_pricing = 'yes'
- _mwf_min_price = '7.00'
- _mwf_recommended_price = '10.00'
- _mwf_max_price = '16.70'
```

**Sync Method:**
```php
// app/Services/WooCommerceApiService.php
protected function syncSolidarityPricingMeta($product)
{
    $wpdb->update(
        'wp_postmeta',
        ['meta_value' => $product->solidarity_min_price],
        ['post_id' => $product->woo_product_id, 'meta_key' => '_mwf_min_price']
    );
    // ... similar for max and recommended prices
}
```

## User Experience Flow

### Scenario 1: Using Defaults
1. Admin enables solidarity pricing on product (£10.00)
2. Min/max fields auto-populate from global settings:
   - Min: £7.00 (70%)
   - Max: £16.70 (167%)
3. Admin saves → syncs to WooCommerce
4. Customer sees price slider: £7.00 - £16.70

### Scenario 2: Custom Per-Product Pricing
1. Admin enables solidarity pricing on product (£10.00)
2. Admin manually overrides min price to £5.00
3. Max price remains auto-calculated (£16.70)
4. Admin saves → syncs to WooCommerce
5. Customer sees price slider: £5.00 - £16.70

### Scenario 3: Changing Global Defaults
1. Admin goes to Settings → changes min to 60%, max to 200%
2. Existing products keep their saved values
3. New products or products with empty min/max fields use new defaults:
   - Min: £6.00 (60%)
   - Max: £20.00 (200%)

## Validation Rules

### Settings Controller
```php
// app/Http/Controllers/Admin/SettingsController.php
'solidarity_min_percent' => 'nullable|integer|min:0|max:100',
'solidarity_max_percent' => 'nullable|integer|min:100|max:500',
```

**Constraints:**
- Min percent: 0-100 (can't exceed 100% of product price)
- Max percent: 100-500 (must be at least 100%, up to 5x product price)

### Product Form
```javascript
// Client-side validation via HTML5
<input type="number" min="0" max="100" step="1">
<input type="number" min="100" max="500" step="1">
```

## Testing Checklist

### ✓ Settings Page
- [ ] Navigate to `/admin/settings`
- [ ] Find "Solidarity Pricing" card in "External Integrations" section
- [ ] Set min to 60%, max to 200%
- [ ] Save settings
- [ ] Verify settings saved: `SELECT * FROM settings WHERE key LIKE 'solidarity%';`

### ✓ Product Edit Form
- [ ] Edit product with £10.00 price
- [ ] Enable solidarity pricing
- [ ] Verify placeholders show:
  - Min: Auto: £6.00 (60%)
  - Max: Auto: £20.00 (200%)
- [ ] Change product price to £15.00
- [ ] Verify placeholders update dynamically:
  - Min: Auto: £9.00 (60%)
  - Max: Auto: £30.00 (200%)

### ✓ WooCommerce Sync
- [ ] Save product with solidarity pricing enabled
- [ ] Run: `php artisan products:sync-to-woocommerce --product=1`
- [ ] Check WordPress postmeta:
  ```sql
  SELECT meta_key, meta_value 
  FROM wp_postmeta 
  WHERE post_id = [woo_product_id] 
  AND meta_key LIKE '_mwf_%';
  ```

### ✓ Frontend Display
- [ ] Visit WooCommerce product page
- [ ] Verify MWF Solidarity Pricing slider appears
- [ ] Check slider range matches saved values
- [ ] Test adding to cart at different price points

## Code References

### Files Modified
1. **resources/views/admin/settings/index.blade.php**
   - Added solidarity pricing settings card
   - Lines: 560-628 (approx)

2. **resources/views/admin/settings/index.cleaned.blade.php**
   - Added solidarity pricing settings card (same as above)

3. **app/Http/Controllers/Admin/SettingsController.php**
   - Added validation rules for solidarity percentages
   - Added storage in $settingsData array

4. **resources/views/admin/products/edit.blade.php**
   - Added PHP code to fetch global settings
   - Added dynamic placeholder calculation
   - Updated JavaScript to use dynamic percentages
   - Added help text with link to settings

### Related Files
- **app/Models/Setting.php** - Setting model
- **app/Services/WooCommerceApiService.php** - WooCommerce sync
- **wp-content/plugins/mwf-solidarity-pricing/** - WordPress plugin
- **WOOCOMMERCE_PRODUCT_SYNC.md** - Complete sync documentation

## Troubleshooting

### Settings Not Appearing
**Issue:** Global settings don't exist in database
**Solution:** They will be created when admin first saves settings page, or use defaults (70%, 167%)

### Placeholders Not Updating
**Issue:** JavaScript not recalculating when product price changes
**Solution:** Check browser console for errors, verify percentages are passed to JavaScript:
```javascript
console.log('Min percent:', {{ $minPercent ?? 70 }});
console.log('Max percent:', {{ $maxPercent ?? 167 }});
```

### Wrong Percentages Applied
**Issue:** Old hardcoded values (70%, 167%) still showing
**Solution:** Clear Laravel cache:
```bash
php artisan cache:clear
php artisan view:clear
```

### WooCommerce Not Syncing
**Issue:** WordPress postmeta not updating
**Solution:** Check `WooCommerceApiService::syncSolidarityPricingMeta()` method, verify database connection in `config/database.php`

## Future Enhancements

1. **Per-Category Defaults**
   - Allow different percentages for different product categories
   - Example: Veg boxes 70-167%, Farm stays 80-150%

2. **Seasonal Adjustments**
   - Schedule percentage changes for specific date ranges
   - Example: Holiday season 60-200%, Summer 70-167%

3. **Customer Segmentation**
   - Different ranges for different customer groups
   - Example: Members 50-150%, Non-members 70-167%

4. **Price History**
   - Track when percentages changed
   - Audit log for pricing adjustments

5. **Analytics Dashboard**
   - Show average customer payment vs recommended price
   - Visualize solidarity pricing adoption rate

## Support

For issues or questions:
- Check `WOOCOMMERCE_PRODUCT_SYNC.md` for sync details
- Review `app/Services/WooCommerceApiService.php` for API logic
- Test with: `php artisan tinker` → `Setting::where('key', 'solidarity_min_percent')->first()`
