#!/bin/bash
# Initialize WooCommerce Tables for Demo Database

echo "Creating WooCommerce tables in wp_demo database..."

# Get MySQL credentials
MYSQL_PWD=$(cat /etc/psa/.psa.shadow)
export MYSQL_PWD

# Source database (production)
SOURCE_DB="wp_pxmxy"
SOURCE_PREFIX="D6sPMX_"

# Target database (demo)
TARGET_DB="wp_demo"
TARGET_PREFIX="demo_wp_"

# WooCommerce tables to copy structure
TABLES=(
    "woocommerce_api_keys"
    "woocommerce_attribute_taxonomies"
    "woocommerce_downloadable_product_permissions"
    "woocommerce_log"
    "woocommerce_order_itemmeta"
    "woocommerce_order_items"
    "woocommerce_payment_tokenmeta"
    "woocommerce_payment_tokens"
    "woocommerce_sessions"
    "woocommerce_shipping_zone_locations"
    "woocommerce_shipping_zone_methods"
    "woocommerce_shipping_zones"
    "woocommerce_tax_rate_locations"
    "woocommerce_tax_rates"
)

for table in "${TABLES[@]}"; do
    echo "Creating ${TARGET_PREFIX}${table}..."
    
    # Get CREATE TABLE statement from source
    mysql -uadmin $SOURCE_DB -e "SHOW CREATE TABLE ${SOURCE_PREFIX}${table}\G" | \
        grep "Create Table" | \
        sed "s/Create Table: //" | \
        sed "s/${SOURCE_PREFIX}/${TARGET_PREFIX}/g" | \
        mysql -uadmin $TARGET_DB
    
    if [ $? -eq 0 ]; then
        echo "  ✓ ${TARGET_PREFIX}${table} created"
    else
        echo "  ✗ Failed to create ${TARGET_PREFIX}${table}"
    fi
done

# Initialize default shipping zones
echo ""
echo "Adding default shipping zone..."
mysql -uadmin $TARGET_DB <<EOF
INSERT INTO ${TARGET_PREFIX}woocommerce_shipping_zones (zone_id, zone_name, zone_order) 
VALUES (0, 'Locations not covered by your other zones', 0)
ON DUPLICATE KEY UPDATE zone_name=zone_name;
EOF

echo ""
echo "✅ WooCommerce tables initialized!"
echo ""
echo "Next: Visit https://soilsync.shop/wp-admin and complete WooCommerce setup wizard"
