#!/bin/bash
# WooCommerce Tables Guardian
# Ensures WooCommerce tables exist and recreates them if missing
# Add this to cron: */5 * * * * /var/www/vhosts/soilsync.shop/admin.soilsync.shop/scripts/woocommerce-tables-guardian.sh

MYSQL_PWD=$(cat /etc/psa/.psa.shadow)
export MYSQL_PWD

SOURCE_DB="wp_pxmxy"
SOURCE_PREFIX="D6sPMX_"
TARGET_DB="wp_demo"
TARGET_PREFIX="demo_wp_"
LOGFILE="/var/log/woocommerce-guardian.log"

# Check if critical WooCommerce table exists
TABLE_EXISTS=$(mysql -uadmin $TARGET_DB -Nse "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$TARGET_DB' AND TABLE_NAME='${TARGET_PREFIX}woocommerce_attribute_taxonomies'")

if [ "$TABLE_EXISTS" -eq 0 ]; then
    echo "$(date): WooCommerce tables missing! Recreating..." >> $LOGFILE
    
    # Copy all WooCommerce table structures from production
    mysqldump -uadmin --no-data $SOURCE_DB $(mysql -uadmin $SOURCE_DB -Nse "SHOW TABLES LIKE '${SOURCE_PREFIX}woocommerce%'") 2>/dev/null | \
        sed "s/${SOURCE_PREFIX}/${TARGET_PREFIX}/g" | \
        mysql -uadmin $TARGET_DB 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo "$(date): ✓ WooCommerce tables recreated successfully" >> $LOGFILE
        
        # Initialize default shipping zone
        mysql -uadmin $TARGET_DB <<EOF 2>/dev/null
INSERT INTO ${TARGET_PREFIX}woocommerce_shipping_zones (zone_id, zone_name, zone_order) 
VALUES (0, 'Locations not covered by your other zones', 0)
ON DUPLICATE KEY UPDATE zone_name=zone_name;
EOF
    else
        echo "$(date): ✗ Failed to recreate WooCommerce tables" >> $LOGFILE
    fi
else
    # Tables exist, just log that we checked
    echo "$(date): ✓ WooCommerce tables OK" >> $LOGFILE
fi

# Keep log file manageable (last 100 lines)
tail -100 $LOGFILE > ${LOGFILE}.tmp && mv ${LOGFILE}.tmp $LOGFILE

exit 0
