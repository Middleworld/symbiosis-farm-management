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

# Create WordPress users for imported subscriptions (required for user switching)
php artisan subscriptions:sync-wp-users --dry-run
php artisan subscriptions:sync-wp-users
```

## User Switching

### How It Works

User switching allows admins to view subscriptions from the customer's perspective:

1. Admin clicks "Switch User" in Laravel delivery schedule
2. Laravel queries WordPress database for user by email
3. Laravel calls AJAX endpoint: `wp-admin/admin-ajax.php?action=mwf_generate_plugin_switch_url`
4. WordPress generates auto-login token (5-minute expiry)
5. Laravel redirects admin to WooCommerce My Account as the customer

### Configuration

**Security:** Uses shared secret key for authentication
- WordPress: `mwf_admin_switch_2025_secret_key` (in `class-mwf-user-switching.php`)
- Laravel: Matches in `WpApiService::generateUserSwitchUrl()`

**For production:** Change this secret in both locations before deployment.

### Troubleshooting

**Error: "No WordPress user found for email"**
- **Cause:** Imported subscriptions don't have WordPress accounts
- **Fix:** Run `php artisan subscriptions:sync-wp-users`

**Error: "Failed to switch user"**
- Verify MWF Integration plugin is activated
- Check `MWF_API_KEY` matches in Laravel `.env` and WordPress `wp-config.php`
- Review WordPress error logs: `wp-content/debug.log`

**Error: "Invalid admin key"**
- Secret key mismatch between Laravel and WordPress
- Verify secret in both `WpApiService.php` and `class-mwf-user-switching.php`

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