# WooCommerce Subscriptions Dependency Testing Plan
## Date: November 20, 2025

## BASELINE (Before Disabling Woo Subscriptions):
- Active subscriptions: 16
- Delivery schedules: 32
- Git commit: 5aad1693 (BACKUP: Working state before WooCommerce Subscriptions dependency testing)

## TESTING CHECKLIST:

### Phase 1: Disable WooCommerce Subscriptions
- [ ] Go to WordPress admin → WooCommerce → Settings → Subscriptions
- [ ] Uncheck "Enable automatic payments"
- [ ] Save settings
- [ ] Verify plugin is effectively disabled

### Phase 2: Test Core Functionality
- [ ] Run: `php artisan vegbox:generate-deliveries`
- [ ] Check: Delivery schedules count (should remain 32)
- [ ] Run: `php artisan vegbox:process-deliveries`
- [ ] Check: Admin interface loads
- [ ] Check: Subscription list displays
- [ ] Check: Payment methods accessible

### Phase 3: Test Subscription Management
- [ ] Create test subscription via POS
- [ ] Check subscription appears in admin
- [ ] Test subscription status changes
- [ ] Verify billing dates calculation

### Phase 4: Test Payment Processing
- [ ] Run: `php artisan vegbox:process-renewals`
- [ ] Check for renewal failures/errors
- [ ] Verify Stripe integration still works
- [ ] Test manual payment processing

### Phase 5: Test Data Synchronization
- [ ] Run: `php artisan vegbox:sync-woo-subscriptions`
- [ ] Check for sync errors
- [ ] Verify subscription data integrity
- [ ] Check WooCommerce data still accessible

## KNOWN DEPENDENCIES TO FIX:
1. Delivery schedule generation
2. Subscription status synchronization
3. Payment method handling
4. Renewal processing
5. Admin interface data display

## RECOVERY PLAN:
If anything breaks:
1. Re-enable WooCommerce Subscriptions immediately
2. Run: `git reset --hard HEAD` (to restore from backup)
3. Document what broke and why
4. Fix the specific issue before next test