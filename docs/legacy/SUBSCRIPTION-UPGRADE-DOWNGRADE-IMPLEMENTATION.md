# Subscription Upgrade/Downgrade Implementation Guide

## 🚨 URGENT FIX NEEDED

The change_plan endpoint is returning success but **NOT SAVING TO DATABASE**. 

### Immediate Action Required:

Check the `handleChangePlan()` method in `VegboxSubscriptionApiController.php` and verify it has:

```php
$subscription->save(); // ← THIS LINE MUST EXIST
```

If the method exists but isn't saving, the issue is likely:
1. Missing `$subscription->save()` call
2. Using wrong model/relationship
3. Database transaction rollback

---

## Current Status

### ✅ WordPress Frontend - COMPLETE
- Change Box Size button implemented
- Modal with all 4 box sizes (correct plan IDs)
- Current plan detection and highlighting
- AJAX handler calling Laravel API
- Error handling improved

### ⚠️ Laravel Backend - PARTIAL (NEEDS FIX)
- Endpoint `/api/subscriptions/{id}/action` exists
- Returns HTTP 200 with success message
- **BUT database not updating**
- Logs show: `{"success":true,"message":"Plan changed successfully..."}`
- Database check shows plan_id unchanged

### Test Results:
```
User clicked: Single Person (plan 227231)
API Response: {"success":true, "message":"Plan changed successfully..."}
Database After: plan_id still 226496 (Small Family) ❌
```

---

## Overview
Add ability for customers to change their vegbox subscription plan (upgrade/downgrade box size) **without creating a new subscription**. This maintains payment history, delivery schedules, and avoids multiple active subscriptions.

## Requirements
- ✅ WordPress API client ready (`change_plan` method added)
- ✅ WordPress AJAX handler ready (`ajax_change_plan` method added)
- ✅ WordPress UI complete (modal with correct plan IDs)
- ⚠️ Laravel API endpoint exists BUT NOT SAVING (needs urgent fix)
- ❌ Laravel controller `save()` call missing or not working

## Correct Plan IDs from Database

**Use these exact IDs** - already configured in WordPress modal:

```php
227231 - Single Person Vegetable Box • Weekly payments • Weekly deliveries (£10/week)
226492 - Couple's Vegetable Box • Weekly payments • Weekly deliveries (£15/week)
226496 - Small Family Vegetable Box • Weekly payments • Weekly deliveries (£22/week)
226499 - Large Family Vegetable Box • Weekly payments • Weekly deliveries (£25/week)
```

## Debugging the Current Issue

### Problem: API returns success but database doesn't update

**Check this in your Laravel controller:**

```bash
cd /opt/sites/admin.middleworldfarms.org
grep -A 10 "function handleChangePlan" app/Http/Controllers/Api/VegboxSubscriptionApiController.php
```

**Look for these lines:**
```php
$subscription->plan_id = $newPlanId;           // ← Assignment happening?
$subscription->price = $newPlan->price;        // ← Assignment happening?
$subscription->save();                         // ← THIS IS CRITICAL!
```

### If save() exists but still not working:

1. **Check model relationship:**
   ```php
   // Is $subscription a VegboxSubscription model?
   dd(get_class($subscription)); // Should be App\Models\VegboxSubscription
   ```

2. **Check if fields are fillable:**
   ```php
   // In VegboxSubscription model, check:
   protected $fillable = ['plan_id', 'price', 'name', 'product_name', ...];
   ```

3. **Check for database triggers/observers:**
   ```bash
   grep -r "VegboxSubscription" app/Observers/
   ```

4. **Add debug logging:**
   ```php
   \Log::info('Before save', ['plan_id' => $subscription->plan_id]);
   $subscription->save();
   \Log::info('After save', ['plan_id' => $subscription->plan_id]);
   ```

5. **Verify database connection:**
   ```php
   // At start of handleChangePlan:
   \Log::info('DB Connection:', [
       'database' => \DB::connection()->getDatabaseName(),
       'subscription_exists' => VegboxSubscription::where('id', $subscription->id)->exists()
   ]);
   ```

---

## Laravel Backend Changes Required

### 1. Update Valid Actions Array

**File:** `app/Http/Controllers/Api/VegboxSubscriptionApiController.php`

**Location:** Inside `handleSubscriptionAction()` method (around line 241)

**Change:**
```php
// BEFORE:
if (!in_array($action, ['pause', 'resume', 'cancel'])) {

// AFTER:
if (!in_array($action, ['pause', 'resume', 'cancel', 'change_plan'])) {
```

### 2. Add Switch Case Handler

**File:** `app/Http/Controllers/Api/VegboxSubscriptionApiController.php`

**Location:** Inside `handleSubscriptionAction()` switch statement (around line 252)

**Add:**
```php
switch ($action) {
    case 'pause':
        return $this->handlePause($request, $subscription);
        
    case 'resume':
        return $this->handleResume($subscription);
        
    case 'cancel':
        return $this->handleCancel($subscription);
    
    case 'change_plan':  // ADD THIS CASE
        return $this->handleChangePlan($request, $subscription);
}
```

### 3. Create Change Plan Handler Method

**File:** `app/Http/Controllers/Api/VegboxSubscriptionApiController.php`

**Location:** After the `handleCancel()` method (around line 330)

**Add complete method:**
```php
/**
 * Handle subscription plan change (upgrade/downgrade)
 * 
 * @param Request $request
 * @param VegboxSubscription $subscription
 * @return \Illuminate\Http\JsonResponse
 */
private function handleChangePlan(Request $request, VegboxSubscription $subscription)
{
    $newPlanId = $request->input('new_plan_id');
    
    // Validate new plan ID
    if (!$newPlanId) {
        return response()->json([
            'success' => false,
            'message' => 'New plan ID is required'
        ], 400);
    }
    
    // Get new plan details
    $newPlan = \App\Models\VegboxPlan::find($newPlanId);
    
    if (!$newPlan) {
        return response()->json([
            'success' => false,
            'message' => 'Plan not found'
        ], 404);
    }
    
    // Prevent "changing" to the same plan
    if ($subscription->vegbox_plan_id == $newPlanId) {
        return response()->json([
            'success' => false,
            'message' => 'Subscription is already on this plan'
        ], 400);
    }
    
    // Store old plan details for logging
    $oldPlanId = $subscription->vegbox_plan_id;
    $oldAmount = $subscription->billing_amount;
    
    try {
        // Update the existing subscription record
        $subscription->vegbox_plan_id = $newPlanId;
        $subscription->product_name = $newPlan->name;
        $subscription->billing_amount = $newPlan->price;
        
        // Update box size if available
        if ($newPlan->box_size) {
            $subscription->variation_name = $newPlan->box_size;
        }
        
        // Keep existing delivery_day, billing_period, billing_interval, status
        // Keep next_billing_date unchanged - they pay new price at next renewal
        
        $subscription->save();
        
        // Log the plan change
        \Log::info('Subscription plan changed', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $newPlanId,
            'old_amount' => $oldAmount,
            'new_amount' => $newPlan->price,
            'changed_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription plan updated successfully',
            'subscription' => [
                'id' => $subscription->id,
                'product_name' => $subscription->product_name,
                'variation_name' => $subscription->variation_name,
                'billing_amount' => $subscription->billing_amount,
                'next_billing_date' => $subscription->next_billing_date
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Failed to change subscription plan', [
            'subscription_id' => $subscription->id,
            'new_plan_id' => $newPlanId,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to update subscription plan'
        ], 500);
    }
}
```

## Important Implementation Notes

### ✅ What This Does Right:
1. **Modifies existing subscription** - No new subscription created
2. **Preserves payment history** - All past payments stay linked
3. **Keeps delivery schedule** - `next_billing_date` unchanged
4. **Updates pricing** - New amount applied at next renewal
5. **Maintains status** - Active stays active, paused stays paused
6. **Logs changes** - Audit trail of all plan changes

### ❌ What NOT to Do:
1. ❌ Don't create a new `VegboxSubscription` record
2. ❌ Don't cancel the old subscription
3. ❌ Don't reset `next_billing_date` (customer shouldn't pay immediately)
4. ❌ Don't change `delivery_day` or `billing_period`
5. ❌ Don't modify WooCommerce orders directly

### Business Logic:
- **Upgrade/Downgrade takes effect at next renewal**
- Customer pays old price until next billing date
- Then automatically charged new price
- No immediate payment required
- No gaps in subscription history

## Testing Checklist

### ⚠️ Current Test Status:

**Test Subscription #151:**
- Current: Small Family (plan_id 226496, but shows £25 - data inconsistency)
- User: Ahmed Sadik (ID 1018)
- Endpoint: `/api/subscriptions/151/action`

**Test 1: Change to Single Person** ❌ FAILED
- Request: `{"action": "change_plan", "new_plan_id": 227231}`
- Response: HTTP 200, `{"success": true, "message": "Plan changed successfully..."}`
- Database: plan_id still 226496 (not changed to 227231)
- **Issue**: `save()` not persisting changes

**Test 2: Try same plan** ✅ WORKS
- Request: Same plan_id again
- Response: HTTP 400, `{"success": false, "message": "Subscription is already on this plan"}`
- **This works** - proves validation logic exists

### Conclusion:
The Laravel controller HAS the `handleChangePlan()` method with validation, but the database update isn't working.

---

### Test Scenarios (Once Fixed):
1. **Upgrade from Small to Large**
   - POST `/api/subscriptions/151/action` with `{"action": "change_plan", "new_plan_id": 3}`
   - Verify billing_amount increases
   - Verify next_billing_date unchanged
   - Verify status remains same

2. **Downgrade from Large to Small**
   - POST `/api/subscriptions/151/action` with `{"action": "change_plan", "new_plan_id": 1}`
   - Verify billing_amount decreases
   - Verify customer keeps existing plan until renewal

3. **Invalid Plan ID**
   - POST with non-existent plan_id
   - Should return 404 error

4. **Same Plan Change**
   - POST with current plan_id
   - Should return 400 error with message

5. **Paused Subscription Change**
   - Change plan on paused subscription
   - Should succeed, apply at resume

### WordPress Integration Test:
```bash
# After Laravel changes deployed, test from WordPress:
curl -X POST 'https://middleworldfarms.org/wp-admin/admin-ajax.php' \
  -H 'Cookie: wordpress_logged_in_xxx' \
  -d 'action=mwf_change_plan' \
  -d 'subscription_id=151' \
  -d 'new_plan_id=2' \
  -d 'nonce=xxx'
```

## Database Considerations

### Fields Updated:
- `vegbox_plan_id` - New plan reference
- `product_name` - New plan name (for display)
- `billing_amount` - New price
- `variation_name` - New box size (if applicable)

### Fields Preserved:
- `id` - Same subscription ID
- `user_id` - Same customer
- `status` - Unchanged
- `delivery_day` - Unchanged
- `billing_period` - Unchanged
- `billing_interval` - Unchanged
- `next_billing_date` - Unchanged (key requirement!)
- `created_at` - Original creation date
- All related payment/order history

## Optional Enhancements (Future)

### Immediate Plan Change with Prorating:
If you want to charge immediately with prorated amounts:

```php
// Calculate days until next billing
$daysRemaining = now()->diffInDays($subscription->next_billing_date);
$totalDays = $subscription->billing_interval * 7; // Assuming weekly

// Calculate proration
$oldDailyRate = $oldAmount / $totalDays;
$newDailyRate = $newPlan->price / $totalDays;
$proratedCharge = ($newDailyRate - $oldDailyRate) * $daysRemaining;

// If upgrade (positive charge), create immediate payment
if ($proratedCharge > 0) {
    // Create WooCommerce order for prorated amount
    // Update next_billing_date to today
}
```

**Note:** Current implementation does NOT prorate - simpler and customer-friendly.

### Plan Change History Table:
Create separate tracking table:

```php
Schema::create('vegbox_subscription_plan_changes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subscription_id')->constrained('vegbox_subscriptions');
    $table->unsignedBigInteger('old_plan_id');
    $table->unsignedBigInteger('new_plan_id');
    $table->decimal('old_amount', 10, 2);
    $table->decimal('new_amount', 10, 2);
    $table->timestamp('changed_at');
    $table->timestamps();
});
```

## WordPress UI Ready

The WordPress side is already implemented with:
- API client method: `change_plan($subscription_id, $new_plan_id)`
- AJAX handler: `ajax_change_plan()`
- Nonce: `mwf_subscriptions`
- Endpoint: `wp_ajax_mwf_change_plan`

Once Laravel is updated, add the UI button in:
`wp-content/plugins/mwf-subscriptions/templates/my-account/subscription-detail.php`

## Quick Fix Verification

**After implementing the fix, test with:**

```bash
# 1. Check current state
cd /opt/sites/admin.middleworldfarms.org
php artisan tinker --execute="echo App\Models\VegboxSubscription::find(151)->plan_id;"
# Should show: 226496 (Small Family)

# 2. Make API call via WordPress (or curl)
curl -X POST 'https://admin.middleworldfarms.org:8444/api/subscriptions/151/action' \
  -H 'X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h' \
  -H 'Content-Type: application/json' \
  -d '{"action":"change_plan","new_plan_id":227231}'

# 3. Verify database changed
php artisan tinker --execute="echo App\Models\VegboxSubscription::find(151)->plan_id;"
# Should now show: 227231 (Single Person) ✅

# 4. Check updated price
php artisan tinker --execute="echo App\Models\VegboxSubscription::find(151)->price;"
# Should now show: 10.00 ✅
```

**If still not working, check Laravel logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "change_plan\|subscription"
```

---

## Deployment Steps

1. ✅ Review this document
2. ⏳ **FIX: Add/verify `$subscription->save()` in Laravel controller**
3. ⏳ **TEST: Verify database updates after API call**
4. ⏳ Test with curl/Postman
5. ⏳ Test from WordPress My Account page
6. ⏳ Deploy to production
7. ⏳ Monitor logs for errors

**Current Blocker:** Step 2 - Database not persisting changes

## Support/Questions

- WordPress API: `/wp-admin/admin-ajax.php?action=mwf_change_plan`
- Laravel API: `POST /api/subscriptions/{id}/action` with `{"action":"change_plan","new_plan_id":X}`
- Authentication: `X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h`

**Key Principle:** One subscription, one customer, one payment stream. No duplicates. Ever.
