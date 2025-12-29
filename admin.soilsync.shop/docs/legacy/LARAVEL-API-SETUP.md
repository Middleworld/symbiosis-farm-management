# Laravel API Setup for MWF Subscriptions

## ModSecurity-Safe Endpoint Structure

The WordPress plugin now uses a single `/action` endpoint to avoid ModSecurity blocking `/resume` and `/cancel` URLs.

---

## API Routes

Add to `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VegboxSubscriptionApiController;

Route::middleware(['api', 'verify.wc.api.token'])->prefix('subscriptions')->group(function () {
    
    // Get user subscriptions
    Route::get('/user/{user_id}', [VegboxSubscriptionApiController::class, 'getUserSubscriptions']);
    
    // Create subscription after WooCommerce order
    Route::post('/create', [VegboxSubscriptionApiController::class, 'createSubscription']);
    
    // Single action endpoint (avoids ModSecurity triggers for /resume and /cancel)
    Route::post('/{id}/action', [VegboxSubscriptionApiController::class, 'handleSubscriptionAction']);
    
    // Get subscription details
    Route::get('/{id}', [VegboxSubscriptionApiController::class, 'getSubscription']);
    
    // Update subscription address
    Route::post('/{id}/update-address', [VegboxSubscriptionApiController::class, 'updateAddress']);
    
    // Get payment history
    Route::get('/{id}/payments', [VegboxSubscriptionApiController::class, 'getPayments']);
});
```

---

## Controller Method

Add to `app/Http/Controllers/Api/VegboxSubscriptionApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VegboxSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VegboxSubscriptionApiController extends Controller
{
    /**
     * Handle subscription actions (pause/resume/cancel)
     * Using single endpoint to avoid ModSecurity blocking /resume and /cancel
     */
    public function handleSubscriptionAction(Request $request, $id)
    {
        $action = $request->input('action');
        
        // Validate action
        if (!in_array($action, ['pause', 'resume', 'cancel'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action. Must be pause, resume, or cancel.'
            ], 400);
        }
        
        try {
            $subscription = VegboxSubscription::findOrFail($id);
            
            switch ($action) {
                case 'pause':
                    return $this->handlePause($request, $subscription);
                    
                case 'resume':
                    return $this->handleResume($subscription);
                    
                case 'cancel':
                    return $this->handleCancel($subscription);
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('API: Subscription action failed', [
                'subscription_id' => $id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process action'
            ], 500);
        }
    }
    
    /**
     * Handle pause action
     */
    private function handlePause(Request $request, VegboxSubscription $subscription)
    {
        $pauseUntil = $request->input('pause_until');
        
        if (!$pauseUntil) {
            return response()->json([
                'success' => false,
                'message' => 'pause_until date is required'
            ], 400);
        }
        
        try {
            $pauseDate = \Carbon\Carbon::parse($pauseUntil);
            
            if ($pauseDate->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pause date must be in the future'
                ], 400);
            }
            
            $subscription->pauseUntil($pauseDate);
            
            Log::info('API: Subscription paused', [
                'subscription_id' => $subscription->id,
                'pause_until' => $pauseUntil
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription paused successfully',
                'pause_until' => $pauseDate->format('Y-m-d')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid pause_until date format'
            ], 400);
        }
    }
    
    /**
     * Handle resume action
     */
    private function handleResume(VegboxSubscription $subscription)
    {
        if (!$subscription->isPaused()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not paused'
            ], 400);
        }
        
        $subscription->resume();
        
        Log::info('API: Subscription resumed', [
            'subscription_id' => $subscription->id
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription resumed successfully'
        ]);
    }
    
    /**
     * Handle cancel action
     */
    private function handleCancel(VegboxSubscription $subscription)
    {
        if ($subscription->canceled_at) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is already cancelled'
            ], 400);
        }
        
        $subscription->update([
            'canceled_at' => now(),
            'ends_at' => now()->addMonth() // Grace period
        ]);
        
        Log::info('API: Subscription cancelled', [
            'subscription_id' => $subscription->id,
            'ends_at' => $subscription->ends_at
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription will be cancelled at the end of the current billing period',
            'ends_at' => $subscription->ends_at->format('Y-m-d')
        ]);
    }
}
```

---

## How WordPress Calls the API

The WordPress plugin sends these requests:

### Pause Subscription
```http
POST /api/subscriptions/123/action
Content-Type: application/json
X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h

{
  "action": "pause",
  "pause_until": "2025-12-01"
}
```

### Resume Subscription
```http
POST /api/subscriptions/123/action
Content-Type: application/json
X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h

{
  "action": "resume"
}
```

### Cancel Subscription
```http
POST /api/subscriptions/123/action
Content-Type: application/json
X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h

{
  "action": "cancel"
}
```

---

## Testing the API

### Test with cURL

```bash
# Pause subscription
curl -X POST "https://admin.middleworldfarms.org:8444/api/subscriptions/1/action" \
  -H "X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h" \
  -H "Content-Type: application/json" \
  -d '{"action": "pause", "pause_until": "2025-12-25"}'

# Resume subscription
curl -X POST "https://admin.middleworldfarms.org:8444/api/subscriptions/1/action" \
  -H "X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h" \
  -H "Content-Type: application/json" \
  -d '{"action": "resume"}'

# Cancel subscription
curl -X POST "https://admin.middleworldfarms.org:8444/api/subscriptions/1/action" \
  -H "X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h" \
  -H "Content-Type: application/json" \
  -d '{"action": "cancel"}'
```

---

## Authentication Middleware

Ensure you have the middleware registered in `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... existing middleware
    'verify.wc.api.token' => \App\Http\Middleware\VerifyWooCommerceApiToken::class,
];
```

And the middleware file at `app/Http/Middleware/VerifyWooCommerceApiToken.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyWooCommerceApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-MWF-API-Key');

        if ($apiKey !== config('services.mwf_api.key')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}
```

---

## Environment Configuration

Add to Laravel `.env`:

```env
# MWF Custom Subscriptions API
MWF_SUBSCRIPTIONS_API_KEY=Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h
```

And to `config/services.php`:

```php
'mwf_api' => [
    'key' => env('MWF_SUBSCRIPTIONS_API_KEY'),
],
```

---

## Why This Approach?

**Problem:** ModSecurity (web application firewall) blocks URLs containing `/resume` and `/cancel` with 403 Forbidden errors.

**Solution:** Use a single `/action` endpoint with the action type in the POST body instead of the URL path.

**Benefits:**
- ✅ Bypasses ModSecurity rules
- ✅ Single endpoint to maintain
- ✅ Cleaner API design
- ✅ Easy to add new actions in the future

---

## Troubleshooting

### Still getting 403 errors?

1. Check Laravel logs: `/opt/sites/admin.middleworldfarms.org/storage/logs/laravel.log`
2. Check if route is registered: `php artisan route:list | grep subscriptions`
3. Verify API key matches between WordPress and Laravel
4. Check Apache/ModSecurity logs for blocked requests

### API returns 401 Unauthorized?

- Verify `X-MWF-API-Key` header is being sent
- Check the API key matches in Laravel `.env` file
- Ensure middleware is registered in `Kernel.php`

### Subscription not found (404)?

- Check if VegboxSubscription model exists
- Verify subscription ID exists in database
- Check database connection in Laravel

---

**WordPress Plugin Status:** ✅ Already configured and active  
**Laravel API Status:** Waiting for deployment with this configuration

---

## Vegbox Plan Sync Command

Use the artisan command below to keep `vegbox_plans` aligned with your existing WooCommerce variation catalogue. Each Vegbox plan is synced using the matching WooCommerce variation ID so the WordPress plugin can send `plan_id = variation_id` without any manual mapping.

```bash
# Preview the changes without saving anything
php artisan vegbox:sync-woo-plans --dry-run --limit=5

# Sync everything (auto-detects vegbox products, but you can target specific ones)
php artisan vegbox:sync-woo-plans

# Only sync a specific WooCommerce product and a couple of variations
php artisan vegbox:sync-woo-plans --product=226081 --variation=226087 --variation=226088
```

### What the command does

- Reads WooCommerce `product_variation` rows (filtered by `--product`/`--variation` if provided)
- Collects each variation's payment option, delivery frequency, and price
- Normalises billing schedules (weekly, fortnightly, monthly, annual) into LaraveLCM-friendly intervals
- Determines a Vegbox box size/slug and upserts the matching `vegbox_plans` row using the variation ID as the plan ID
- Reports created, updated, restored, skipped, and unchanged plans at the end of the run

### Helpful options

- `--dry-run` &mdash; See what would change without touching the database.
- `--product=` &mdash; Limit by WooCommerce parent product ID (repeatable or comma-separated).
- `--variation=` &mdash; Limit to specific variation IDs (repeatable).
- `--limit=` &mdash; Process only the first _n_ matching variations (handy for spot checks).

> **Tip:** If no products are currently marked with the `_is_vegbox_subscription` meta flag, the command automatically scans all published WooCommerce variations. Use `--product` to narrow the scope on large stores.
