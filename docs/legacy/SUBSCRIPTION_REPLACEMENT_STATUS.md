# WooCommerce Subscriptions Replacement - Current Status
**Date:** November 29, 2025  
**Status:** ⚠️ **INCOMPLETE - Still Dependent on WooCommerce Subscriptions**

---

## 🎯 Goal
Replace paid WooCommerce Subscriptions GPL add-on with free, open-source subscription system.

---

## ✅ What's Complete

### 1. Laravel Backend Subscription System
- ✅ Core subscription models (`VegboxSubscription`, `VegboxPlan`)
- ✅ Payment processing service with MWF Funds API integration
- ✅ Automated renewal command (`vegbox:process-renewals`)
- ✅ Grace period & retry logic (7 days, 3 attempts)
- ✅ Admin dashboard with subscription management
- ✅ Manual renewal/cancellation controls
- ✅ Failed payment tracking and notifications

**Location:** `/opt/sites/admin.middleworldfarms.org/`
- Models: `app/Models/Vegbox*.php`
- Commands: `app/Console/Commands/ProcessSubscriptionRenewals.php`
- Services: `app/Services/VegboxPaymentService.php`
- Controllers: `app/Http/Controllers/Admin/VegboxSubscriptionController.php`

### 2. WordPress Custom Plugin Started
- ✅ Plugin skeleton created: `mwf-subscriptions` v1.1.0
- ✅ API integration configured
- ✅ Checkout hooks written (delivery day selection)
- ✅ My Account hooks written (subscription display)
- ⚠️ **BUT: Not actively working/capturing data**

**Location:** `/var/www/vhosts/middleworldfarms.org/httpdocs/wp-content/plugins/mwf-subscriptions/`

---

## ❌ What's Missing - Why We Still Need WooCommerce Subscriptions

### Critical Gap: Checkout Integration
**Problem:** New subscriptions (like Molly Francis today) are created by **WooCommerce Subscriptions**, NOT our custom system.

**Evidence:**
- Subscription 228097 created today has NO custom metadata:
  - ❌ Missing: `customer_week_type`
  - ❌ Missing: `preferred_collection_day`  
  - ❌ Missing: `delivery_frequency`
- Had to manually add these fields after creation
- Our `mwf-subscriptions` WordPress plugin isn't capturing checkout data

**Root Cause:**
1. WooCommerce Subscriptions still handles checkout
2. Our plugin hooks exist but aren't firing/working
3. Variable product attributes aren't being saved to subscription metadata
4. No integration between WooCommerce checkout and our Laravel backend

### What Happens Now (Current Flow):
```
Customer Checkout
    ↓
WooCommerce Subscriptions creates subscription
    ↓
❌ Our plugin SHOULD capture: delivery day, frequency, week type
    ❌ Our plugin SHOULD call Laravel API: /api/subscriptions/create
    ↓
Subscription missing custom fields
    ↓
Manual intervention required (like we did for Molly today)
```

### What SHOULD Happen (Target Flow):
```
Customer Checkout
    ↓
Our mwf-subscriptions plugin captures checkout data
    ↓
Plugin calls Laravel API: POST /api/subscriptions/create
    ↓
Laravel creates VegboxSubscription record
    ↓
WooCommerce subscription created WITH custom metadata
    ↓
Subscription appears in delivery schedule correctly
```

---

## 🔧 Work Still Required

### Phase 1: Fix WordPress Plugin ⏳
**Priority: HIGH - Blocking removal of WooCommerce Subscriptions**

**Tasks:**
1. ✅ Debug why checkout hooks aren't firing
   - Check if plugin is active
   - Verify hook priorities
   - Test `woocommerce_checkout_order_processed` hook
   
2. ✅ Capture product variation attributes at checkout
   - Get `pa_payment-option` (Weekly/Fortnightly/Monthly)
   - Get `pa_frequency` (Weekly box/Fortnightly box)
   - Determine delivery vs collection from shipping method
   - Capture preferred day (Friday/Saturday)

3. ✅ Save to subscription metadata
   - `customer_week_type`: Weekly/A/B (derived from frequency)
   - `preferred_collection_day`: Friday/Saturday
   - `delivery_frequency`: Weekly box/Fortnightly box

4. ✅ Call Laravel API
   - POST `/api/subscriptions/create`
   - Send all subscription data
   - Handle response/errors

5. ✅ Test with real checkout
   - Create test subscription
   - Verify metadata is saved
   - Verify appears in delivery schedule
   - Verify Laravel VegboxSubscription created

### Phase 2: Renewals & Payments ⏳
**Priority: MEDIUM - Can use WooCommerce for now**

**Current State:**
- Laravel handles renewals via cron
- Uses MWF Funds API for payment
- Grace period & retries working
- **BUT:** Only works for subscriptions created in Laravel

**Tasks:**
1. Sync existing WooCommerce subscriptions to Laravel
   - Migration command needed
   - One-time data import
   
2. Test renewal processing
   - Verify Laravel can renew WooCommerce-created subscriptions
   - Test payment deduction
   - Test grace period logic

3. Disable WooCommerce automatic renewals
   - Let Laravel handle all renewals
   - Keep WooCommerce for display/management only

### Phase 3: Customer Portal ⏳
**Priority: LOW - Can use WooCommerce My Account**

**Tasks:**
1. Build Laravel customer portal
   - View subscriptions
   - Pause/resume
   - Change delivery options
   - Update payment methods

2. Replace WooCommerce My Account pages
   - Redirect to Laravel portal
   - Maintain seamless UX

### Phase 4: Remove WooCommerce Subscriptions ⏳
**Priority: FINAL STEP**

**Prerequisites:**
- ✅ All new subscriptions created via our system
- ✅ All renewals handled by Laravel
- ✅ Customer portal functional
- ✅ 30+ days of successful operation
- ✅ Full data backup

**Steps:**
1. Export all subscription data
2. Deactivate WooCommerce Subscriptions plugin
3. Test for 14 days
4. Delete plugin
5. Remove GPL license

---

## 📊 Current Dependency Breakdown

| Feature | WooCommerce Subs | Our System | Status |
|---------|-----------------|------------|--------|
| **Checkout/Creation** | ✅ Active | ❌ Not Working | 🔴 Blocking |
| **Subscription Storage** | ✅ Database | ✅ Laravel DB | 🟡 Dual System |
| **Renewals** | ⚠️ Can disable | ✅ Working | 🟢 Ready |
| **Payment Processing** | ⚠️ Not used | ✅ MWF Funds | 🟢 Ready |
| **Customer Portal** | ✅ My Account | ❌ Not Built | 🔴 Blocking |
| **Admin Management** | ✅ WP Admin | ✅ Laravel | 🟢 Ready |
| **Delivery Schedule** | ⚠️ Data source | ✅ Generator | 🟡 Depends on WC data |

---

## 🎯 Next Steps (Priority Order)

### IMMEDIATE (This Week):
1. **Activate & test mwf-subscriptions plugin**
   - Verify it's enabled in WordPress
   - Check error logs
   - Test checkout flow

2. **Fix checkout integration**
   - Debug why hooks aren't capturing data
   - Test on staging with new subscription
   - Verify metadata saves correctly

3. **Test Laravel API integration**
   - Verify plugin can reach Laravel
   - Check API authentication
   - Test subscription creation

### SHORT TERM (Next 2 Weeks):
4. **Migrate existing subscriptions**
   - Create sync command
   - Import all active subscriptions to Laravel
   - Verify data integrity

5. **Switch to Laravel renewals**
   - Disable WooCommerce automatic renewals
   - Monitor Laravel cron job
   - Test grace period handling

### MEDIUM TERM (1-2 Months):
6. **Build customer portal**
   - Laravel-based My Account
   - Subscription management UI
   - Payment method updates

7. **Full testing period**
   - 30 days operation
   - Monitor errors/issues
   - Gather user feedback

### FINAL:
8. **Remove WooCommerce Subscriptions**
   - Export all data
   - Deactivate plugin
   - Cancel GPL license
   - Save £££

---

## 💡 Key Insights

### Why We're Not Ready Yet:
1. **Checkout is the blocker** - We can't create subscriptions without WooCommerce
2. **Customer portal missing** - Users need self-service options
3. **Not fully tested** - Need production validation before removing WC

### What's Working Well:
1. **Laravel backend is solid** - Renewals, payments, grace period all working
2. **Admin tools are good** - Better than WooCommerce admin
3. **Cost savings proven** - £0 licensing vs GPL fees

### Realistic Timeline:
- **Fix checkout:** 1 week
- **Migrate data:** 1 week  
- **Test renewals:** 2 weeks
- **Build portal:** 3-4 weeks
- **Validation:** 4 weeks
- **Total:** ~2-3 months to full independence

---

## 📝 Technical Debt

### Code Quality Issues:
1. **Duplicate detection logic** - Delivery vs collection checked in multiple places
2. **Missing custom metadata** - Subscriptions created without required fields
3. **Manual fixes required** - Like Molly Francis today
4. **No validation** - Can create subscriptions missing critical data

### Database Issues:
1. **Two sources of truth** - WooCommerce AND Laravel databases
2. **Sync problems** - Data can get out of sync
3. **Migration complexity** - Historical data in WC format

### Integration Issues:
1. **Shipping method detection** - Recently improved but was unreliable
2. **WordPress plugin not working** - Hooks exist but don't fire
3. **API authentication** - May have connectivity issues

---

## 🎓 Lessons Learned

### What Worked:
- ✅ Starting with Laravel backend first
- ✅ Building admin tools early
- ✅ Grace period implementation
- ✅ Comprehensive documentation

### What Didn't Work:
- ❌ Assuming WordPress plugin would "just work"
- ❌ Not testing checkout integration earlier
- ❌ Underestimating WooCommerce dependencies

### What To Do Differently:
- 🔄 Test checkout integration FIRST
- 🔄 Build customer portal earlier
- 🔄 More incremental testing
- 🔄 Parallel run systems longer

---

## 🚀 Recommended Action Plan

### This Weekend:
```bash
# 1. Check if mwf-subscriptions plugin is active
wp plugin list --path=/var/www/vhosts/middleworldfarms.org/httpdocs/

# 2. Enable WP_DEBUG and check logs
tail -f /var/www/vhosts/middleworldfarms.org/httpdocs/wp-content/debug.log

# 3. Test checkout on staging
# Create test subscription
# Check if hooks fire
# Verify metadata saves
```

### Next Week:
1. Fix WordPress plugin checkout hooks
2. Test with real checkout flow
3. Verify Laravel API receives data
4. Create one successful end-to-end subscription

### Next Month:
1. Migrate all existing subscriptions
2. Disable WooCommerce renewals
3. Monitor Laravel handling everything
4. Start building customer portal

---

## 📞 Support Contacts

**Developer:** Check `/opt/sites/admin.middleworldfarms.org/` Laravel app  
**WordPress Plugin:** `/var/www/vhosts/middleworldfarms.org/httpdocs/wp-content/plugins/mwf-subscriptions/`  
**Documentation:** See `VEGBOX_DOCUMENTATION_INDEX.md` for full docs  
**Testing Plan:** See `WOO_DEPENDENCY_TEST_PLAN.md`

---

**Bottom Line:** We're 70% there, but the critical 30% (checkout integration) is what's blocking us from removing WooCommerce Subscriptions. The good news is the hard part (backend) is done. The remaining work is WordPress/WooCommerce integration, which is well-understood territory.
