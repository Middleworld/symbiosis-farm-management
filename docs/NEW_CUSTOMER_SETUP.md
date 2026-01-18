# MWF Platform - New Customer Setup Guide

## Quick Setup (5 Minutes)

### Option 1: Automated Setup Script (Recommended)

```bash
# Run the automated setup script
cd /path/to/wordpress
/path/to/mwf-platform/scripts/setup-new-wordpress-site.sh
```

This script automatically:
- ✅ Enables WooCommerce REST API
- ✅ Configures permalinks
- ✅ Adds WooCommerce capabilities to admin users
- ✅ Generates API keys with correct permissions
- ✅ Activates required plugins
- ✅ Syncs all variable products
- ✅ Clears caches
- ✅ Saves credentials to `.env-woocommerce-api`

**After running the script:**
1. Copy the API credentials to your Laravel `.env` file
2. Run `php artisan config:clear`
3. Done!

---

### Option 2: Manual Setup

If you prefer manual setup or need to troubleshoot:

#### 1. Install WordPress & WooCommerce

```bash
# Install WordPress (if not already installed)
wp core download
wp core config --dbname=database --dbuser=user --dbpass=password
wp core install --url="https://example.com" --title="Farm Shop" --admin_user=admin --admin_email=admin@example.com

# Install WooCommerce
wp plugin install woocommerce --activate
```

#### 2. Enable WooCommerce REST API

```bash
wp option update woocommerce_api_enabled yes
```

#### 3. Configure Permalinks

```bash
wp rewrite structure '/%year%/%monthnum%/%day%/%postname%/'
wp rewrite flush
```

#### 4. Add WooCommerce Capabilities to Admin

```bash
ADMIN_ID=$(wp user list --role=administrator --field=ID | head -1)
wp user add-cap $ADMIN_ID manage_woocommerce
wp user add-cap $ADMIN_ID edit_shop_orders
wp user add-cap $ADMIN_ID read_shop_orders
wp user add-cap $ADMIN_ID edit_products
wp user add-cap $ADMIN_ID read_products
```

#### 5. Generate API Keys

**Via WordPress Admin:**
1. Go to WooCommerce → Settings → Advanced → REST API
2. Click "Add key"
3. Description: `MWF Platform API`
4. User: Select administrator
5. Permissions: **Read/Write** (IMPORTANT!)
6. Click "Generate API key"
7. Copy Consumer Key and Consumer Secret immediately

**Via WP-CLI:**
```bash
# Generate random keys
CONSUMER_KEY="ck_$(openssl rand -hex 32 | cut -c1-43)"
CONSUMER_SECRET="cs_$(openssl rand -hex 32 | cut -c1-43)"

# Hash the secret
HASHED_SECRET=$(php -r "echo hash('sha256', '$CONSUMER_SECRET');")

# Get admin user ID
ADMIN_ID=$(wp user list --role=administrator --field=ID | head -1)

# Get table prefix
TABLE_PREFIX=$(wp db prefix)

# Insert API key
wp db query "INSERT INTO ${TABLE_PREFIX}woocommerce_api_keys (user_id, description, permissions, consumer_key, consumer_secret, truncated_key) VALUES ($ADMIN_ID, 'MWF Platform API', 'read_write', '$CONSUMER_KEY', '$HASHED_SECRET', '$(echo $CONSUMER_KEY | tail -c 8)')"

echo "Consumer Key: $CONSUMER_KEY"
echo "Consumer Secret: $CONSUMER_SECRET"
```

#### 6. Install MWF Plugins

```bash
# Copy plugin files
cp -r /path/to/mwf-subscriptions /path/to/wordpress/wp-content/plugins/
cp -r /path/to/mwf-solidarity-pricing /path/to/wordpress/wp-content/plugins/

# Activate plugins
wp plugin activate mwf-subscriptions
wp plugin activate mwf-solidarity-pricing
```

**Note:** The `mwf-subscriptions` plugin activation hook will automatically:
- Enable WooCommerce REST API (if not already enabled)
- Add WooCommerce capabilities to administrators
- Sync all variable products
- Configure permalinks
- Clear caches

#### 7. Configure Laravel Admin

Add to Laravel `.env`:

```env
# WordPress Database Connection
WP_DB_HOST=127.0.0.1
WP_DB_PORT=3306
WP_DB_DATABASE=wordpress_db
WP_DB_USERNAME=wordpress_user
WP_DB_PASSWORD=your_password
WP_DB_PREFIX=wp_

# WooCommerce REST API
WOOCOMMERCE_URL=https://your-shop-domain.com
WOOCOMMERCE_CONSUMER_KEY=ck_your_consumer_key_here
WOOCOMMERCE_CONSUMER_SECRET=cs_your_consumer_secret_here

# MWF Integration API (for custom subscription sync)
MWF_API_KEY=your_generated_api_key_here
```

#### 8. Test Connection

```bash
cd /path/to/laravel-admin
php artisan config:clear
php artisan tinker

# Test WooCommerce API
$service = app(\App\Services\WooCommerceApiService::class);
$result = $service->getProducts(['per_page' => 1]);
echo json_encode($result, JSON_PRETTY_PRINT);
```

Expected output:
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "name": "Product Name",
            ...
        }
    ]
}
```

---

## Common Issues & Solutions

### Issue: "Sorry, you cannot list resources"

**Cause:** API keys have wrong permissions or REST API is disabled

**Solution:**
```bash
# Check API enabled
wp option get woocommerce_api_enabled

# If 'no', enable it
wp option update woocommerce_api_enabled yes

# Check key permissions
wp db query "SELECT permissions FROM wp_woocommerce_api_keys WHERE user_id = 1"

# Should return 'read_write', not 'read'
```

### Issue: Products show "out of stock" despite being variable products

**Cause:** Variations not synced or stock status incorrect

**Solution:**
```bash
# Sync all variable products
wp eval 'foreach([239, 240, 241] as $id) { $product = wc_get_product($id); if ($product && $product->is_type("variable")) { WC_Product_Variable::sync($product); } }'

# Set stock status to instock
wp db query "UPDATE wp_postmeta SET meta_value = 'instock' WHERE post_id IN (239, 240, 241) AND meta_key = '_stock_status'"

# Clear caches
wp cache flush
wp transient delete --all
```

### Issue: Solidarity pricing slider not updating with variation changes

**Cause:** Browser caching old JavaScript

**Solution:**
1. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
2. Clear WordPress caches: `wp cache flush`
3. Check browser console for JavaScript errors (F12)

### Issue: Variations have no prices

**Cause:** Variations not properly created or synced

**Solution:**
```bash
# Check variations exist
wp db query "SELECT COUNT(*) FROM wp_posts WHERE post_type = 'product_variation' AND post_parent = 239"

# If zero, sync from Laravel
cd /path/to/laravel-admin
php artisan tinker

use App\Models\Product;
use App\Services\WooCommerceApiService;
$service = app(WooCommerceApiService::class);
$product = Product::find(1);
$result = $service->syncProduct($product);
echo json_encode($result, JSON_PRETTY_PRINT);
```

---

## Theme Installation

### Option 1: Install Astra Theme (Recommended for WooCommerce)

```bash
wp theme install astra --activate
wp plugin install astra-sites --activate
```

### Option 2: Use MWF Custom Theme

```bash
cp -r /path/to/mwf-theme /path/to/wordpress/wp-content/themes/
wp theme activate mwf-theme
```

---

## Post-Setup Checklist

- [ ] WooCommerce REST API enabled (`woocommerce_api_enabled = yes`)
- [ ] Permalinks configured (not default `?p=123`)
- [ ] API keys generated with **read_write** permissions
- [ ] Admin user has WooCommerce capabilities
- [ ] MWF plugins activated (`mwf-subscriptions`, `mwf-solidarity-pricing`)
- [ ] Laravel `.env` configured with WooCommerce credentials
- [ ] Laravel config cache cleared (`php artisan config:clear`)
- [ ] Test API connection successful
- [ ] Products created and synced
- [ ] Product variations visible and purchasable
- [ ] Solidarity pricing slider working
- [ ] Test checkout flow with Stripe test cards

---

## Deployment Script for Multiple Customers

Create a deployment script for bulk customer onboarding:

```bash
#!/bin/bash
# deploy-new-customer.sh

CUSTOMER_NAME=$1
CUSTOMER_DOMAIN=$2

echo "Setting up MWF Platform for $CUSTOMER_NAME ($CUSTOMER_DOMAIN)"

# 1. Create WordPress installation
wp core download --path="/var/www/$CUSTOMER_NAME"
cd "/var/www/$CUSTOMER_NAME"

# 2. Configure database
wp core config \
  --dbname="${CUSTOMER_NAME}_db" \
  --dbuser="${CUSTOMER_NAME}_user" \
  --dbpass="$(openssl rand -base64 32)"

# 3. Install WordPress
wp core install \
  --url="https://$CUSTOMER_DOMAIN" \
  --title="$CUSTOMER_NAME Farm Shop" \
  --admin_user=admin \
  --admin_password="$(openssl rand -base64 32)" \
  --admin_email="admin@$CUSTOMER_DOMAIN"

# 4. Run automated setup
/path/to/mwf-platform/scripts/setup-new-wordpress-site.sh "/var/www/$CUSTOMER_NAME"

# 5. Import products from template
wp plugin install wordpress-importer --activate
wp import /path/to/product-template.xml --authors=create

echo "Setup complete for $CUSTOMER_NAME!"
```

Usage:
```bash
./deploy-new-customer.sh "Green Valley Farm" "greenvaleyfarm.com"
```

---

## Support & Troubleshooting

If issues persist after following this guide:

1. Check WordPress error logs: `tail -f /path/to/wordpress/wp-content/debug.log`
2. Check Laravel logs: `tail -f /path/to/laravel/storage/logs/laravel.log`
3. Enable WordPress debug mode in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
4. Test API connectivity with curl:
   ```bash
   curl -u "ck_key:cs_secret" https://your-shop.com/wp-json/wc/v3/products
   ```

---

## Version History

- **v1.0** - Initial setup guide
- **v1.1** - Added automated setup script
- **v1.2** - Added plugin activation hooks for auto-configuration
- **v1.3** - Added troubleshooting section and deployment script
