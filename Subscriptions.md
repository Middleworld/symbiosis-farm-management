# MWF Custom Subscriptions Plugin

**Version:** 1.0.0  
**Replaces:** WooCommerce Subscriptions addon  
**Savings:** £199/year  

A custom WordPress plugin for managing vegbox subscriptions with a Laravel-powered backend API.

---

## 🎯 Overview

This plugin provides subscription management features that integrate seamlessly with WooCommerce checkout and My Account pages. It communicates with a Laravel backend API (`admin.middleworldfarms.org:8444`) to handle subscription logic, allowing customers to:

- Select delivery days during checkout
- View active subscriptions in My Account
- Pause/resume subscriptions with date selection
- Cancel subscriptions
- View payment history

---

## 📦 Installation

### 1. Upload Plugin

Upload the `mwf-subscriptions` folder to:
```
/wp-content/plugins/mwf-subscriptions/
```

### 2. Activate Plugin

Go to **WordPress Admin > Plugins** and activate **MWF Custom Subscriptions**.

### 3. Flush Rewrite Rules

After activation, go to **Settings > Permalinks** and click **Save Changes** to flush rewrite rules.

---

## ⚙️ Configuration

### Product Setup

For each vegbox product that should create a subscription:

1. Go to **Products > Edit Product**
2. Add custom fields (in Custom Fields metabox or using ACF):
   ```
   _is_vegbox_subscription = yes
   _vegbox_plan_id = 1
   ```
   
   **Note:** The `_vegbox_plan_id` must match a valid plan ID in your Laravel database.

3. Save the product

### API Configuration

The plugin uses these constants (defined in `mwf-subscriptions.php`):

```php
define('MWF_SUBS_API_URL', 'https://admin.middleworldfarms.org:8444/api/subscriptions');
define('MWF_SUBS_API_KEY', 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h');
```

Update these if your Laravel API URL or key changes.

---

## 🚀 Usage

### Customer Experience

#### Checkout Flow:
1. Customer adds vegbox product to cart
2. During checkout, they see "Delivery Options" section
3. Customer selects preferred delivery day (Monday-Friday)
4. After order completes, subscription is automatically created via API

#### My Account:
1. Customer navigates to **My Account > Subscriptions**
2. Sees list of all subscriptions with status, next payment, etc.
3. Can click "View" to see subscription details
4. Can pause (select resume date), resume, or cancel subscription

### Actions Available:

- **Pause**: Customer selects a future date to resume
- **Resume**: Immediately reactivates a paused subscription
- **Cancel**: Cancels at end of current billing period

---

## 🔧 Technical Details

### File Structure

```
mwf-subscriptions/
├── mwf-subscriptions.php          # Main plugin file
├── includes/
│   ├── class-mwf-api-client.php   # Laravel API client
│   ├── class-mwf-my-account.php   # My Account integration
│   └── class-mwf-checkout.php     # Checkout integration
├── templates/
│   ├── my-account-subscriptions.php    # Subscriptions list
│   └── my-account-subscription-view.php # Subscription details
└── assets/
    ├── css/
    │   └── mwf-subscriptions.css   # Styling
    └── js/
        └── mwf-subscriptions.js    # AJAX interactions
```

### Hooks Used

**WooCommerce:**
- `woocommerce_account_menu_items` - Add Subscriptions tab
- `woocommerce_after_order_notes` - Add delivery day selector
- `woocommerce_checkout_process` - Validate delivery day
- `woocommerce_checkout_update_order_meta` - Save delivery day
- `woocommerce_order_status_completed` - Create subscription
- `woocommerce_order_status_processing` - Create subscription

**WordPress:**
- `wp_ajax_mwf_pause_subscription` - AJAX pause handler
- `wp_ajax_mwf_resume_subscription` - AJAX resume handler
- `wp_ajax_mwf_cancel_subscription` - AJAX cancel handler

### Custom Endpoints

- `/my-account/subscriptions/` - List subscriptions
- `/my-account/view-subscription/{id}/` - View subscription details

### API Communication

All API requests include the header:
```
X-MWF-API-Key: Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h
```

**API Endpoints Used:**
- `GET /api/subscriptions/user/{user_id}` - Get user subscriptions
- `POST /api/subscriptions/create` - Create subscription
- `POST /api/subscriptions/{id}/pause` - Pause subscription
- `POST /api/subscriptions/{id}/resume` - Resume subscription
- `POST /api/subscriptions/{id}/cancel` - Cancel subscription
- `GET /api/subscriptions/{id}` - Get subscription details

---

## 🐛 Debugging

### Enable WordPress Debug Mode

In `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Logs

**WordPress logs:**
```
/wp-content/debug.log
```

**Laravel logs:**
```
/opt/sites/admin.middleworldfarms.org/storage/logs/laravel.log
```

### Common Issues

**1. "Subscriptions" tab not appearing**
- Flush rewrite rules: Settings > Permalinks > Save
- Check if WooCommerce is active

**2. Subscription not created after order**
- Check product has `_is_vegbox_subscription = yes` meta
- Check product has valid `_vegbox_plan_id`
- Check order status is "completed" or "processing"
- Check WordPress debug log for API errors

**3. API errors (401 Unauthorized)**
- Verify API key matches between WordPress and Laravel
- Check Laravel middleware is registered

**4. AJAX actions not working**
- Check browser console for JavaScript errors
- Verify nonce is being passed correctly
- Check WordPress AJAX admin URL is correct

---

## 🔐 Security

- All AJAX requests verify WordPress nonces
- API requests require authentication via `X-MWF-API-Key` header
- User permissions checked before displaying subscription data
- Input sanitization on all user-submitted data
- Date validation before sending to API

---

## 🧪 Testing Checklist

### Before Going Live:

- [ ] Install and activate plugin
- [ ] Flush rewrite rules
- [ ] Configure product with custom fields
- [ ] Test checkout with vegbox product
- [ ] Verify subscription created in Laravel
- [ ] Check "Subscriptions" tab appears in My Account
- [ ] Test pause subscription
- [ ] Test resume subscription
- [ ] Test cancel subscription
- [ ] Verify API logs show no errors
- [ ] Test on mobile devices
- [ ] Test with different user roles

---

## 📋 Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 8.0+
- Laravel API deployed and accessible
- Active Laravel backend with subscription tables

---

## 🆘 Support

For issues or questions:

1. Check debug logs first
2. Verify Laravel API is responding
3. Check product custom fields are set correctly
4. Review implementation guide: `MWF_SUBSCRIPTIONS_IMPLEMENTATION.md`

---

## 📝 Notes

- This plugin does NOT handle payment processing - that's done by WooCommerce
- Subscription renewals are managed by Laravel backend
- Account funds integration is preserved from existing `mwf-integration` plugin
- This plugin works alongside (but replaces) WooCommerce Subscriptions addon

---

## 🎉 Next Steps

After plugin is working:

1. **Deactivate WooCommerce Subscriptions addon** (£199/year savings!)
2. Monitor logs for any issues
3. Set up email notifications for subscription events (in Laravel)
4. Add admin features for subscription management
5. Consider adding skip delivery feature
6. Add product swapping capability

---

**Built with ❤️ by Middle World Farms**
