# Vegbox Subscription System - Quick Reference

**Version:** 1.0  
**Status:** ✅ Production Ready  
**License:** MIT (Open Source)

---

## 📁 Key Files Reference

### Models
```
app/Models/VegboxSubscription.php    - Main subscription model with retry logic
app/Models/VegboxPlan.php            - Subscription plan model
```

### Services
```
app/Services/VegboxPaymentService.php - Payment processing with MWF API
```

### Controllers
```
app/Http/Controllers/Admin/VegboxSubscriptionController.php - 7 admin actions
```

### Commands
```
app/Console/Commands/ProcessSubscriptionRenewals.php - Daily renewals (8 AM)
app/Console/Commands/TestGracePeriod.php             - Testing utility
```

### Notifications
```
app/Notifications/SubscriptionRenewed.php          - Success notification
app/Notifications/SubscriptionPaymentFailed.php    - Failure notification
app/Notifications/LowBalanceWarning.php            - Low balance alert
app/Notifications/SubscriptionCancelled.php        - Cancellation notice
app/Notifications/DailyRenewalSummary.php          - Admin summary
```

### Views
```
resources/views/admin/vegbox-subscriptions/
├── index.blade.php              - Main dashboard
├── show.blade.php               - Subscription details
├── failed-payments.blade.php    - Failed payments list
└── upcoming-renewals.blade.php  - Upcoming renewals
```

### Configuration
```
config/subscription.php          - Grace period settings
.env                             - Environment variables
```

### Documentation
```
VEGBOX_SUBSCRIPTION_PROJECT_PLAN.md         - Original project plan
VEGBOX_SUBSCRIPTION_COMPLETION_SUMMARY.md   - Complete implementation details
WOOCOMMERCE_SUBSCRIPTION_MIGRATION.md       - WooCommerce add-on removal guide
GRACE_PERIOD_IMPLEMENTATION.md              - Grace period system details
```

---

## 🚀 Quick Commands

### Daily Operations
```bash
# Process renewals manually
php artisan vegbox:process-renewals

# Test grace period system
php artisan vegbox:test-grace-period {subscription_id}

# View scheduled tasks
php artisan schedule:list

# Check logs
tail -f storage/logs/laravel.log
```

### Maintenance
```bash
# Clear caches
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear

# Run all caches at once
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database
```bash
# Access Laravel Tinker
php artisan tinker

# Query subscriptions
>>> VegboxSubscription::active()->count()
>>> VegboxSubscription::inGracePeriod()->get()
>>> VegboxSubscription::readyForRetry()->get()
```

---

## 🌐 URLs

```
Admin Dashboard:      https://admin.middleworldfarms.org:8444/admin/vegbox-subscriptions
Failed Payments:      https://admin.middleworldfarms.org:8444/admin/vegbox-subscriptions/failed
Upcoming Renewals:    https://admin.middleworldfarms.org:8444/admin/vegbox-subscriptions/upcoming
```

---

## ⚙️ Configuration Values

### Grace Period Settings (config/subscription.php)
```php
'grace_period_days' => 7        // Days before auto-cancel
'max_retry_attempts' => 3       // Number of retry attempts
'retry_delays' => [2, 4, 6]     // Days between retries
```

### Environment Variables (.env)
```env
SUBSCRIPTION_GRACE_PERIOD_DAYS=7
SUBSCRIPTION_MAX_RETRY_ATTEMPTS=3
SUBSCRIPTION_RETRY_DELAYS="2,4,6"
ADMIN_EMAIL=middleworldfarms@gmail.com

# MWF API
MWF_API_KEY=your_api_key
MWF_API_URL=https://middleworldfarms.org/wp-json/mwf/v1
```

---

## 📊 Database Tables

### vegbox_subscriptions
```sql
Key columns:
- id, user_id, plan_id
- next_billing_at              (when to charge next)
- failed_payment_count         (retry tracking)
- next_retry_at               (when to retry)
- grace_period_ends_at        (auto-cancel date)
- last_payment_error          (error message)
```

### vegbox_plans
```sql
Key columns:
- id, name, description
- price, currency
- billing_interval, billing_period
```

---

## 🔔 Notification Types

1. **SubscriptionRenewed** - Customer: successful renewal
2. **SubscriptionPaymentFailed** - Customer: payment failure + retry info
3. **LowBalanceWarning** - Customer: balance < £50
4. **SubscriptionCancelled** - Customer: subscription ended
5. **DailyRenewalSummary** - Admin: daily summary email

---

## 🎯 Grace Period Flow

```
Day 0: Payment fails
├── Record failure (#1)
├── Schedule retry for Day 2
└── Send failure notification

Day 2: Retry attempt #1
├── Payment fails again
├── Record failure (#2)
├── Schedule retry for Day 6
└── Send retry notification

Day 6: Retry attempt #2
├── Payment fails again
├── Record failure (#3)
├── Schedule retry for Day 12
└── Send final warning

Day 7: Grace period ends
├── Auto-cancel subscription
├── Send cancellation notification
└── Update status

Day 12: Retry attempt #3 (if within grace period)
└── Last attempt before auto-cancel
```

---

## ✅ Testing Checklist

### Before Each Release
- [ ] Run `php artisan test` (if tests exist)
- [ ] Test grace period: `php artisan vegbox:test-grace-period 1`
- [ ] Check scheduled tasks: `php artisan schedule:list`
- [ ] Verify admin dashboard loads
- [ ] Test manual renewal
- [ ] Check failed payments view
- [ ] Verify notifications sent (check logs)
- [ ] Clear all caches: `php artisan optimize:clear`

---

## 🐛 Troubleshooting

### Renewals Not Processing
```bash
# Check if scheduler is running
crontab -l | grep schedule:run

# Run manually to see errors
php artisan vegbox:process-renewals

# Check logs
tail -f storage/logs/laravel.log
```

### Payment Failures
```bash
# Test MWF API connectivity
curl -X POST https://middleworldfarms.org/wp-json/mwf/v1/funds \
  -H "X-WC-API-Key: your_key" \
  -d "action=check&email=test@example.com"

# Check payment service
php artisan tinker
>>> $service = new App\Services\VegboxPaymentService();
>>> $service->checkBalance('test@example.com');
```

### Dashboard Errors
```bash
# Clear all caches
php artisan optimize:clear

# Regenerate autoloader
composer dump-autoload

# Check permissions
chmod -R 775 storage bootstrap/cache
```

---

## 📈 Monitoring

### Daily Checks
- [ ] Check admin email for daily summary
- [ ] Review failed payments dashboard
- [ ] Monitor grace period expirations
- [ ] Verify renewal processing logs

### Weekly Checks
- [ ] Review upcoming renewals (7 days)
- [ ] Check retry success rates
- [ ] Monitor customer notifications
- [ ] Review error logs

### Monthly Checks
- [ ] Revenue reconciliation
- [ ] Customer churn analysis
- [ ] Database optimization
- [ ] Backup verification

---

## 🔐 Security Notes

### API Keys
- MWF API key stored in `.env` (never commit!)
- X-WC-API-Key header for authentication
- SSL/TLS for all API calls

### Database Access
- WordPress connection read-only where possible
- Proper escaping for all queries
- Use Eloquent ORM to prevent SQL injection

### Admin Access
- Protected by admin.auth middleware
- Session-based authentication
- HTTPS required (port 8444)

---

## 💡 Tips & Best Practices

### Performance
- Use query scopes for reusable queries
- Eager load relationships to avoid N+1
- Cache frequently accessed data
- Index database columns used in WHERE clauses

### Reliability
- Always use try-catch for external API calls
- Log errors for debugging
- Send admin notifications on critical failures
- Keep backups of configuration

### Maintenance
- Monitor error logs regularly
- Keep Laravel and packages updated
- Run database optimizations monthly
- Document all customizations

---

## 📞 Quick Support

### Error: "View not found"
```bash
php artisan view:clear
php artisan view:cache
```

### Error: "Route not found"
```bash
php artisan route:clear
php artisan route:cache
```

### Error: "Class not found"
```bash
composer dump-autoload
php artisan optimize:clear
```

### Error: "Database connection failed"
Check `.env` file:
```env
WP_DB_HOST=localhost
WP_DB_DATABASE=wp_pxmxy
WP_DB_USERNAME=wp_pteke
WP_DB_PASSWORD=***
WP_DB_PREFIX=D6sPMX_
```

---

**Last Updated:** November 5, 2025  
**Maintained By:** Middle World Farms Development Team
