# Laravel API Endpoint Needed for WooCommerce Subscription Migration

## Endpoint: POST /api/subscriptions/migrate

**Purpose:** Accept WooCommerce subscription data and create corresponding Laravel subscriptions during migration.

**Location:** Add to your Laravel admin routes (`routes/api.php`)

---

## Route Definition

```php
// In routes/api.php
Route::middleware(['api', 'verify.wc.api.token'])->prefix('subscriptions')->group(function () {
    // ... existing routes ...
    
    // Migration endpoint
    Route::post('/migrate', [VegboxSubscriptionApiController::class, 'migrateFromWooCommerce']);
});
```

---

## Controller Method

```php
// In app/Http/Controllers/Api/VegboxSubscriptionApiController.php

/**
 * Migrate a WooCommerce subscription to Laravel
 * 
 * POST /api/subscriptions/migrate
 */
public function migrateFromWooCommerce(Request $request)
{
    $validated = $request->validate([
        'woocommerce_subscription_id' => 'required|integer',
        'woocommerce_customer_id' => 'required|integer',
        'customer_email' => 'required|email',
        'customer_first_name' => 'nullable|string',
        'customer_last_name' => 'nullable|string',
        'product_id' => 'nullable|integer',
        'product_name' => 'required|string',
        'variation_id' => 'nullable|integer',
        'status' => 'required|string',
        'billing_frequency' => 'required|string',
        'delivery_frequency' => 'required|string',
        'total' => 'required|numeric',
        'next_payment_date' => 'nullable|date',
        'start_date' => 'nullable|date',
        'billing_address' => 'nullable|array',
        'shipping_address' => 'nullable|array',
    ]);
    
    try {
        // Check if already migrated (prevent duplicates)
        $existing = VegboxSubscription::where('woocommerce_subscription_id', $validated['woocommerce_subscription_id'])->first();
        
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription already migrated',
                'subscription_id' => $existing->id,
                'subscription' => $existing
            ], 200);
        }
        
        // Create new subscription
        $subscription = VegboxSubscription::create([
            'woocommerce_subscription_id' => $validated['woocommerce_subscription_id'],
            'woocommerce_customer_id' => $validated['woocommerce_customer_id'],
            'customer_email' => $validated['customer_email'],
            'customer_first_name' => $validated['customer_first_name'],
            'customer_last_name' => $validated['customer_last_name'],
            'woocommerce_product_id' => $validated['product_id'],
            'product_name' => $validated['product_name'],
            'variation_id' => $validated['variation_id'],
            'status' => $validated['status'],
            'billing_frequency' => $validated['billing_frequency'],
            'delivery_frequency' => $validated['delivery_frequency'],
            'total' => $validated['total'],
            'currency' => 'GBP',
            'next_payment_date' => $validated['next_payment_date'],
            'start_date' => $validated['start_date'] ?? now(),
            'billing_address' => json_encode($validated['billing_address'] ?? []),
            'shipping_address' => json_encode($validated['shipping_address'] ?? []),
            'is_migrated' => true,
            'migrated_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription migrated successfully',
            'subscription_id' => $subscription->id,
            'subscription' => $subscription
        ], 201);
        
    } catch (\Exception $e) {
        Log::error('Subscription migration failed', [
            'wc_subscription_id' => $validated['woocommerce_subscription_id'],
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage()
        ], 500);
    }
}
```

---

## Database Migration

**If the `vegbox_subscriptions` table doesn't have these columns yet, add them:**

```php
// Create migration: php artisan make:migration add_woocommerce_fields_to_vegbox_subscriptions

public function up()
{
    Schema::table('vegbox_subscriptions', function (Blueprint $table) {
        $table->unsignedBigInteger('woocommerce_subscription_id')->nullable()->after('id');
        $table->unsignedBigInteger('woocommerce_customer_id')->nullable()->after('woocommerce_subscription_id');
        $table->unsignedBigInteger('woocommerce_product_id')->nullable()->after('product_name');
        $table->boolean('is_migrated')->default(false)->after('status');
        $table->timestamp('migrated_at')->nullable()->after('is_migrated');
        
        // Add index for faster lookups
        $table->index('woocommerce_subscription_id');
    });
}

public function down()
{
    Schema::table('vegbox_subscriptions', function (Blueprint $table) {
        $table->dropColumn([
            'woocommerce_subscription_id',
            'woocommerce_customer_id',
            'woocommerce_product_id',
            'is_migrated',
            'migrated_at'
        ]);
    });
}
```

**Run:** `php artisan migrate`

---

## Model Update

```php
// In app/Models/VegboxSubscription.php

protected $fillable = [
    // ... existing fields ...
    'woocommerce_subscription_id',
    'woocommerce_customer_id',
    'woocommerce_product_id',
    'is_migrated',
    'migrated_at',
];

protected $casts = [
    // ... existing casts ...
    'is_migrated' => 'boolean',
    'migrated_at' => 'datetime',
];
```

---

## Test the Endpoint

```bash
curl -X POST "https://admin.middleworldfarms.org:8444/api/subscriptions/migrate" \
  -H "Content-Type: application/json" \
  -H "X-WC-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h" \
  -d '{
    "woocommerce_subscription_id": 228084,
    "woocommerce_customer_id": 28,
    "customer_email": "laurstratford@gmail.com",
    "customer_first_name": "Laura",
    "customer_last_name": "Stratford",
    "product_id": 123,
    "product_name": "Small Family Vegetable Box",
    "variation_id": 456,
    "status": "active",
    "billing_frequency": "weekly",
    "delivery_frequency": "weekly",
    "total": 22.00,
    "next_payment_date": "2025-12-10",
    "start_date": "2025-11-25",
    "billing_address": {"postcode": "SW1A 1AA"},
    "shipping_address": {"postcode": "SW1A 1AA"}
  }'
```

---

## What Happens Next

1. **Add this endpoint to Laravel admin**
2. **Run the WordPress migration script:** 
   ```bash
   cd /var/www/vhosts/middleworldfarms.org/httpdocs
   wp eval-file wp-content/plugins/mwf-subscriptions/migrate-wc-subscriptions.php --allow-root
   ```
3. **Script will:**
   - Find all 21 active WooCommerce subscriptions
   - Call your Laravel API `/migrate` endpoint for each one
   - Create Laravel subscriptions with `woocommerce_subscription_id` link
   - Add `_migrated_to_laravel_id` meta to WooCommerce subscription
   
4. **Result:**
   - Laura's subscription #228084 will show on My Subscriptions page
   - All 21 subscriptions synced to Laravel
   - WooCommerce subscriptions remain as backup (not deleted)

---

## Notes

- The migration is **non-destructive** - WooCommerce subscriptions stay intact
- Subscriptions are linked via `woocommerce_subscription_id` field
- Future renewals should use Laravel system, but WooCommerce subscriptions still work
- You can run migration multiple times safely (endpoint checks for duplicates)
