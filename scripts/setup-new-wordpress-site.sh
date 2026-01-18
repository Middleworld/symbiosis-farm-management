#!/bin/bash
# Automated WordPress/WooCommerce Setup for MWF Platform
# This script configures a fresh WordPress installation with all necessary settings

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== MWF Platform WordPress Setup ===${NC}"

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo -e "${RED}Error: WP-CLI is not installed${NC}"
    exit 1
fi

# Get WordPress path from argument or use current directory
WP_PATH=${1:-$(pwd)}
cd "$WP_PATH"

echo -e "${YELLOW}WordPress path: $WP_PATH${NC}"

# Verify this is a WordPress installation
if ! wp core is-installed --allow-root 2>/dev/null; then
    echo -e "${RED}Error: Not a valid WordPress installation${NC}"
    exit 1
fi

echo -e "${GREEN}✓ WordPress installation verified${NC}"

# 1. Enable WooCommerce REST API
echo -e "${YELLOW}Enabling WooCommerce REST API...${NC}"
wp option update woocommerce_api_enabled yes --allow-root
echo -e "${GREEN}✓ WooCommerce REST API enabled${NC}"

# 2. Ensure permalinks are configured
echo -e "${YELLOW}Configuring permalinks...${NC}"
PERMALINK_STRUCTURE=$(wp option get permalink_structure --allow-root)
if [ -z "$PERMALINK_STRUCTURE" ]; then
    wp rewrite structure '/%year%/%monthnum%/%day%/%postname%/' --allow-root
    wp rewrite flush --allow-root
    echo -e "${GREEN}✓ Permalinks configured${NC}"
else
    echo -e "${GREEN}✓ Permalinks already configured${NC}"
fi

# 3. Get admin user ID (usually 1)
ADMIN_USER=$(wp user list --role=administrator --field=ID --allow-root | head -1)
if [ -z "$ADMIN_USER" ]; then
    echo -e "${RED}Error: No administrator user found${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Admin user found: ID $ADMIN_USER${NC}"

# 4. Add WooCommerce capabilities to admin
echo -e "${YELLOW}Adding WooCommerce capabilities to admin user...${NC}"
wp user add-cap "$ADMIN_USER" manage_woocommerce --allow-root
wp user add-cap "$ADMIN_USER" edit_shop_orders --allow-root
wp user add-cap "$ADMIN_USER" read_shop_orders --allow-root
wp user add-cap "$ADMIN_USER" edit_products --allow-root
wp user add-cap "$ADMIN_USER" read_products --allow-root
echo -e "${GREEN}✓ WooCommerce capabilities added${NC}"

# 5. Generate WooCommerce API keys
echo -e "${YELLOW}Generating WooCommerce API keys...${NC}"

# Check if keys already exist
EXISTING_KEYS=$(wp db query "SELECT COUNT(*) as count FROM ${DB_PREFIX:-wp_}woocommerce_api_keys WHERE user_id = $ADMIN_USER AND permissions = 'read_write'" --allow-root --skip-column-names 2>/dev/null || echo "0")

if [ "$EXISTING_KEYS" -gt 0 ]; then
    echo -e "${YELLOW}⚠ API keys already exist. Generating new keys...${NC}"
fi

# Generate new keys using WooCommerce REST API consumer creation
CONSUMER_KEY=$(openssl rand -hex 32 | cut -c1-43)
CONSUMER_SECRET=$(openssl rand -hex 32 | cut -c1-43)
CONSUMER_KEY="ck_${CONSUMER_KEY}"
CONSUMER_SECRET="cs_${CONSUMER_SECRET}"

# Hash the consumer secret
HASHED_SECRET=$(php -r "echo hash('sha256', '$CONSUMER_SECRET');")

# Get table prefix
TABLE_PREFIX=$(wp db prefix --allow-root)

# Insert API key into database
wp db query "INSERT INTO ${TABLE_PREFIX}woocommerce_api_keys (user_id, description, permissions, consumer_key, consumer_secret, truncated_key) 
VALUES ($ADMIN_USER, 'MWF Platform API', 'read_write', '$CONSUMER_KEY', '$HASHED_SECRET', '$(echo $CONSUMER_KEY | tail -c 8)')" --allow-root

echo -e "${GREEN}✓ WooCommerce API keys generated${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}Consumer Key:    ${CONSUMER_KEY}${NC}"
echo -e "${GREEN}Consumer Secret: ${CONSUMER_SECRET}${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}⚠ Save these credentials - they won't be shown again!${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Save to .env-wordpress file for reference
cat > "$WP_PATH/.env-woocommerce-api" << EOF
# WooCommerce API Credentials
# Generated: $(date)
WOOCOMMERCE_URL=$(wp option get siteurl --allow-root)
WOOCOMMERCE_CONSUMER_KEY=$CONSUMER_KEY
WOOCOMMERCE_CONSUMER_SECRET=$CONSUMER_SECRET
EOF
chmod 600 "$WP_PATH/.env-woocommerce-api"
echo -e "${GREEN}✓ Credentials saved to .env-woocommerce-api${NC}"

# 6. Activate required plugins if present
echo -e "${YELLOW}Checking for required plugins...${NC}"
REQUIRED_PLUGINS=("woocommerce" "mwf-subscriptions" "mwf-solidarity-pricing")

for plugin in "${REQUIRED_PLUGINS[@]}"; do
    if wp plugin is-installed "$plugin" --allow-root 2>/dev/null; then
        if ! wp plugin is-active "$plugin" --allow-root 2>/dev/null; then
            wp plugin activate "$plugin" --allow-root
            echo -e "${GREEN}✓ Activated plugin: $plugin${NC}"
        else
            echo -e "${GREEN}✓ Plugin already active: $plugin${NC}"
        fi
    else
        echo -e "${YELLOW}⚠ Plugin not found: $plugin (skipping)${NC}"
    fi
done

# 7. Sync variable products if WooCommerce is active
if wp plugin is-active woocommerce --allow-root 2>/dev/null; then
    echo -e "${YELLOW}Syncing variable products...${NC}"
    VARIABLE_PRODUCTS=$(wp db query "SELECT ID FROM ${TABLE_PREFIX}posts WHERE post_type = 'product' AND post_status = 'publish'" --allow-root --skip-column-names 2>/dev/null || echo "")
    
    if [ -n "$VARIABLE_PRODUCTS" ]; then
        SYNC_COUNT=0
        for product_id in $VARIABLE_PRODUCTS; do
            wp eval 'foreach(['"$product_id"'] as $id) { $product = wc_get_product($id); if ($product && $product->is_type("variable")) { WC_Product_Variable::sync($product); } }' --allow-root 2>/dev/null || true
            ((SYNC_COUNT++))
        done
        echo -e "${GREEN}✓ Synced $SYNC_COUNT variable products${NC}"
    else
        echo -e "${YELLOW}⚠ No products found to sync${NC}"
    fi
fi

# 8. Clear all caches
echo -e "${YELLOW}Clearing caches...${NC}"
wp cache flush --allow-root 2>/dev/null || true
wp transient delete --all --allow-root 2>/dev/null || true
echo -e "${GREEN}✓ Caches cleared${NC}"

# 9. Flush rewrite rules
echo -e "${YELLOW}Flushing rewrite rules...${NC}"
wp rewrite flush --allow-root
echo -e "${GREEN}✓ Rewrite rules flushed${NC}"

echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   WordPress Setup Complete! ✓             ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "1. Copy API credentials from .env-woocommerce-api to your Laravel .env"
echo -e "2. Run: php artisan config:clear"
echo -e "3. Test WooCommerce API connection from Laravel admin"
echo ""
echo -e "${YELLOW}Laravel .env configuration:${NC}"
echo -e "WOOCOMMERCE_URL=$(wp option get siteurl --allow-root)"
echo -e "WOOCOMMERCE_CONSUMER_KEY=$CONSUMER_KEY"
echo -e "WOOCOMMERCE_CONSUMER_SECRET=$CONSUMER_SECRET"
echo ""
