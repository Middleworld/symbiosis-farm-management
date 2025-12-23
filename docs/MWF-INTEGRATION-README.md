# MWF Integration Plugin

This document outlines the custom REST API endpoints that should be implemented in the MWF-integration WordPress plugin to provide enhanced WooCommerce integration capabilities.

## Overview

The MWF Integration plugin extends WooCommerce's REST API with custom endpoints that provide:

- Enhanced product editing capabilities
- Advanced WooCommerce admin features
- Seamless integration with the Middle World Farms admin interface
- Bulk operations and automation tools

## Installation

1. Create a new WordPress plugin file: `wp-content/plugins/mwf-integration/mwf-integration.php`
2. Copy the sample code from `docs/mwf-integration-plugin-sample.php`
3. Activate the plugin in WordPress admin
4. Configure the API key in Settings > MWF Integration

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