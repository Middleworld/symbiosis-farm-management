# MWF Integration Plugin

This document outlines the MWF-integration WordPress plugin that provides enhanced WooCommerce integration capabilities for the Laravel admin system.

## Overview

The MWF Integration plugin provides:

- **User Switching**: Secure admin impersonation for customer support
- **WordPress User Sync**: Automatic WordPress account creation for subscriptions
- Enhanced product editing capabilities
- Advanced WooCommerce admin features
- Seamless integration with the Middle World Farms admin interface
- Bulk operations and automation tools

## Required WordPress Plugins

### 1. MWF Integration Suite (`mwf-integration`)
Location: `wp-content/plugins/mwf-integration/`

**Features:**
- User switching via AJAX endpoints
- Email validation
- WooCommerce enhancements

**Files:**
- `mwf-integration.php` - Main plugin file
- `includes/class-mwf-user-switching.php` - AJAX-based user switching
- `includes/class-mwf-email-validation.php`
- `includes/class-mwf-woocommerce-integration.php`

### 2. MWF Custom Subscriptions (`mwf-subscriptions`)
Location: `wp-content/plugins/mwf-subscriptions/`

**Purpose:** Replaces WooCommerce Subscriptions plugin with Laravel-backed subscription management

**Configuration** (in `wp-config.php`):
```php
// Laravel Admin API URL
define('MWF_API_URL', 'https://admin.yourdomain.com/api/subscriptions');
define('MWF_API_KEY', 'your_api_key_here');
```

## Installation

### WordPress Plugin Installation

1. Copy plugins to WordPress:
```bash
cp -r wordpress-plugins/mwf-integration /path/to/wordpress/wp-content/plugins/
cp -r wordpress-plugins/mwf-subscriptions /path/to/wordpress/wp-content/plugins/
```

2. Activate via WP-CLI or WordPress admin:
```bash
wp plugin activate mwf-integration mwf-subscriptions
```

3. Configure in `wp-config.php`:
```php
// Laravel API Integration
define('MWF_API_URL', 'https://admin.yourdomain.com/api/subscriptions');
define('MWF_API_KEY', 'your_secure_api_key_here');
```

### Laravel Configuration

In `.env`:
```bash
# WordPress Database Connection (Read-Only)
WORDPRESS_DB_HOST=127.0.0.1
WORDPRESS_DB_DATABASE=wordpress_db
WORDPRESS_DB_USERNAME=wordpress_user
WORDPRESS_DB_PASSWORD=secure_password
WORDPRESS_DB_PREFIX=wp_

# WooCommerce Site
WOOCOMMERCE_URL=https://your-woocommerce-site.com/

# MWF Integration API Key (must match WordPress)
MWF_INTEGRATION_API_KEY=your_secure_api_key_here
```

### Initial Data Setup

After installation:

```bash
# Import existing subscriptions from WooCommerce
php artisan vegbox:import-woo-subscriptions --dry-run
php artisan vegbox:import-woo-subscriptions

# Create WordPress users for imported subscriptions
# REQUIRED for user switching to work - creates wp_users records with correct table prefix
php artisan subscriptions:sync-wp-users --dry-run
php artisan subscriptions:sync-wp-users
```

**Important:** The `sync-wp-users` command automatically uses the WordPress table prefix from your `.env` configuration (`WP_DB_PREFIX`). It will create user meta with keys like `{prefix}capabilities` and `{prefix}user_level` to match your WordPress installation.

## User Switching

### How It Works

User switching allows admins to impersonate customers and view their WooCommerce My Account page:

1. **Admin Action**: Admin clicks "Switch to User" button in Laravel admin panel (customers page or delivery schedule)
2. **Token Generation**: 
   - Laravel calls WordPress AJAX endpoint: `wp-admin/admin-ajax.php?action=mwf_generate_plugin_switch_url`
   - WordPress plugin generates secure auto-login token (5-minute expiry)
   - Token stored in WordPress options table with user ID and redirect path
3. **Auto-Login URL**: Laravel receives URL like `https://site.com/?mwf_auto_login=TOKEN&redirect_to=/my-account/`
4. **Customer Impersonation**:
   - WordPress plugin intercepts request in constructor (before `init` hook)
   - Validates token and checks expiry
   - Logs in as customer using `wp_set_current_user()` and `wp_set_auth_cookie()`
   - Redirects to My Account page
5. **Customer View**: Admin sees the site as the customer, fully logged in

### Technical Implementation

**Critical:** The `handle_auto_login()` method is called **directly in the constructor** of `MWF_User_Switching` class, not on the `init` hook. This is because the class is instantiated during `init`, so adding another `init` hook would be too late.

```php
// In class-mwf-user-switching.php constructor:
public function __construct() {
    // Call immediately - don't wait for 'init' hook
    $this->handle_auto_login();
    $this->init_hooks();
}
```

### Security

**Shared Secret Key:** `mwf_admin_switch_2025_secret_key`
- Must match in both Laravel (`WpApiService.php`) and WordPress (`class-mwf-user-switching.php`)
- Used to generate admin key: `hash('sha256', $userId . $redirectTo . $secret)`
- **Change this for production deployments**

**Token Security:**
- Tokens expire after 5 minutes
- One-time use (deleted after consumption)
- Stored in WordPress options table: `mwf_auto_login_token_{TOKEN}`

### Configuration

**WordPress Side** (`wp-content/plugins/mwf-integration/includes/class-mwf-user-switching.php`):
- Secret key in `get_admin_switch_key()` method
- Token expiry: `$token_expiry = time() + 300;` (5 minutes)

**Laravel Side** (`app/Services/WpApiService.php`):
- Secret key in `generateUserSwitchUrl()` method
- AJAX endpoint: `{$this->apiUrl}/wp-admin/admin-ajax.php`
- Appends `redirect_to` parameter to returned URL

### Troubleshooting

**Error: "No WordPress user found for email"**
- **Cause:** Imported subscriptions don't have WordPress accounts
- **Fix:** Run `php artisan subscriptions:sync-wp-users`
- **Note:** WordPress users need correct table prefix (e.g., `demo_wp_capabilities` not `wp_capabilities`)

**User switches but lands on homepage instead of My Account**
- **Cause:** WordPress plugin not processing auto-login token
- **Check:** WordPress debug log at `wp-content/debug.log` for "MWF Auto Login" messages
- **Fix:** Ensure MWF Integration plugin is activated
- **Fix:** Verify plugin version has auto-login in constructor, not on `init` hook

**Error: "Failed to switch user"**
- **Check:** MWF Integration plugin is activated in WordPress
- **Check:** `MWF_API_KEY` matches in Laravel `.env` and WordPress `wp-config.php`
- **Review:** WordPress error logs: `wp-content/debug.log`

**Error: "Invalid admin key"**
- **Cause:** Secret key mismatch between Laravel and WordPress
- **Fix:** Verify secret in both `WpApiService.php` and `class-mwf-user-switching.php` match exactly

**Cookies not persisting / Login form shows after redirect**
- **Cause:** Browser not maintaining cookies (shouldn't happen in real browsers, only curl tests)
- **Fix:** Test in actual browser, not with curl
- **Note:** Plugin fires `do_action('wp_login')` to ensure all WordPress login hooks execute

## API Endpoints

All endpoints require Bearer token authentication:

```
Authorization: Bearer {api_key}
```

### Products

#### GET `/wp-json/mwf-integration/v1/products/{id}/edit`

Get enhanced product data for editing, including all WooCommerce fields.

**Response:**
```json
{
  "id": 123,
  "name": "Product Name",
  "sku": "PROD-001",
  "description": "Full HTML description",
  "short_description": "Short description",
  "price": "29.99",
  "regular_price": "39.99",
  "sale_price": "29.99",
  "stock_quantity": 100,
  "manage_stock": true,
  "stock_status": "instock",
  "categories": [...],
  "images": {...},
  "attributes": [...],
  "variations": [...],
  "meta_data": [...],
  "product_type": "simple",
  "status": "publish",
  // ... all WooCommerce product fields
}
```

#### PUT `/wp-json/mwf-integration/v1/products/{id}`

Update product with enhanced data.

**Request Body:**
```json
{
  "name": "Updated Product Name",
  "description": "Updated HTML description",
  "price": "24.99",
  "stock_quantity": 150,
  "categories": [1, 2, 3],
  "images": {
    "featured": 456,
    "gallery": [789, 101]
  },
  "attributes": [...],
  "meta_data": [...]
}
```

#### GET `/wp-json/mwf-integration/v1/products/{id}/variations`

Get all variations for a variable product.

#### POST `/wp-json/mwf-integration/v1/products/bulk-update`

Bulk update multiple products.

**Request Body:**
```json
{
  "products": [
    {
      "id": 123,
      "updates": {
        "price": "19.99",
        "stock_quantity": 50
      }
    },
    {
      "id": 456,
      "updates": {
        "status": "draft",
        "stock_status": "outofstock"
      }
    }
  ]
}
```

### System

#### GET `/wp-json/mwf-integration/v1/capabilities`

Get system capabilities and supported features.

**Response:**
```json
{
  "woocommerce_version": "8.0.0",
  "wordpress_version": "6.4.0",
  "php_version": "8.2.0",
  "features": {
    "variable_products": true,
    "product_variations": true,
    "bulk_operations": true,
    "advanced_attributes": true,
    "custom_meta": true,
    "product_images": true,
    "categories": true,
    "shipping_classes": true,
    "tax_classes": true
  },
  "supported_actions": [
    "update_product",
    "bulk_update",
    "get_variations",
    "update_variation",
    "duplicate_product",
    "delete_product",
    "update_stock",
    "update_price"
  ]
}
```

#### POST `/wp-json/mwf-integration/v1/actions`

Execute WooCommerce admin actions.

**Request Body:**
```json
{
  "action": "regenerate_thumbnails",
  "params": {}
}
```

**Supported Actions:**
- `regenerate_thumbnails` - Regenerate all product image thumbnails
- `clear_transients` - Clear WooCommerce transients
- `update_product_lookup_tables` - Update product lookup tables
- `recalculate_stock_levels` - Recalculate stock levels for variable products

## Configuration

### API Key Setup

1. Go to WordPress Admin > Settings > MWF Integration
2. The plugin will auto-generate an API key on activation
3. Copy this key to your Laravel `.env` file:

```env
MWF_API_KEY=your_generated_api_key_here
```

### Laravel Configuration

Add to `config/services.php`:

```php
'woocommerce' => [
    'base_url' => env('WOOCOMMERCE_BASE_URL'),
    'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
    'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
    'mwf_api_key' => env('MWF_API_KEY'),
],
```

## Security

- All endpoints require Bearer token authentication
- API keys should be kept secure and rotated regularly
- Consider IP whitelisting for additional security
- Use HTTPS for all API communications

## Error Handling

All endpoints return standard HTTP status codes:

- `200` - Success
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (invalid API key)
- `404` - Not Found
- `500` - Internal Server Error

Error responses include:
```json
{
  "code": "error_code",
  "message": "Human readable message",
  "data": {
    "status": 400
  }
}
```

## Testing

Use tools like Postman or curl to test endpoints:

```bash
curl -X GET \
  https://your-site.com/wp-json/mwf-integration/v1/capabilities \
  -H "Authorization: Bearer your_api_key"
```

## Benefits

This custom API provides:

1. **Enhanced Product Editing**: Access to all WooCommerce product fields
2. **Bulk Operations**: Efficiently update multiple products
3. **Advanced Features**: Variable products, attributes, variations
4. **Admin Actions**: Execute WooCommerce maintenance tasks
5. **Seamless Integration**: Direct connection without iframe limitations
6. **Better Performance**: Optimized API calls vs. standard WooCommerce REST API

## Implementation Notes

- The plugin extends WooCommerce's existing functionality
- All standard WooCommerce hooks and filters are respected
- Product updates trigger standard WooCommerce actions
- Compatible with WooCommerce extensions and themes
- Follows WordPress coding standards and security practices</content>
<parameter name="filePath">/opt/sites/admin.middleworldfarms.org/docs/MWF-INTEGRATION-README.md