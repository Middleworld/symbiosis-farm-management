#!/bin/bash

###############################################################################
# Deploy Updates from Gold Master to Production
# 
# This script deploys tested improvements from soilsync.shop (gold master)
# back to middleworldfarms.org (production) or any customer site.
#
# Usage:
#   ./deploy-updates.sh --list                    # Show available updates
#   ./deploy-updates.sh --update bulk-sync        # Deploy specific update
#   ./deploy-updates.sh --update all              # Deploy all updates
#   ./deploy-updates.sh --target /path/to/site    # Custom target path
###############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GOLD_MASTER="/var/www/vhosts/soilsync.shop"
PRODUCTION="/var/www/vhosts/middleworldfarms.org"

# Target site (can be overridden with --target)
TARGET_SITE="$PRODUCTION"
UPDATE_NAME=""
LIST_ONLY=false

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

###############################################################################
# Parse arguments
###############################################################################
while [[ $# -gt 0 ]]; do
    case $1 in
        --list)
            LIST_ONLY=true
            shift
            ;;
        --update)
            UPDATE_NAME="$2"
            shift 2
            ;;
        --target)
            TARGET_SITE="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --list                 List all available updates"
            echo "  --update NAME          Deploy specific update"
            echo "  --target PATH          Target site path (default: production)"
            echo "  --help                 Show this help"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

###############################################################################
# Available Updates Registry
###############################################################################

declare -A UPDATES

# Update: Bulk Sync Feature
UPDATES[bulk-sync]="Bulk product sync to WooCommerce"
update_bulk_sync() {
    echo "Deploying: Bulk Sync Feature"
    
    # 1. Update ProductController with bulkSyncWithWooCommerce method
    echo "  → Updating ProductController..."
    local controller="$TARGET_SITE/admin.soilsync.shop/app/Http/Controllers/Admin/ProductController.php"
    if [ -f "$controller" ]; then
        cp "$GOLD_MASTER/admin.soilsync.shop/app/Http/Controllers/Admin/ProductController.php" "$controller.new"
        echo "     ✓ Controller updated (review .new file, then rename)"
    else
        echo "     ⚠ Controller not found at: $controller"
    fi
    
    # 2. Update products index view with bulk actions UI
    echo "  → Updating products/index.blade.php..."
    local view="$TARGET_SITE/admin.soilsync.shop/resources/views/admin/products/index.blade.php"
    if [ -f "$view" ]; then
        cp "$GOLD_MASTER/admin.soilsync.shop/resources/views/admin/products/index.blade.php" "$view.new"
        echo "     ✓ View updated (review .new file, then rename)"
    else
        echo "     ⚠ View not found at: $view"
    fi
    
    # 3. Route already exists in routes/web.php (check manually)
    echo "  → Check route exists: POST /admin/products/bulk-sync-woocommerce"
}

# Update: Short Description Sync Fix
UPDATES[short-description]="Fix short description sync to WooCommerce"
update_short_description() {
    echo "Deploying: Short Description Sync Fix"
    
    local service="$TARGET_SITE/admin.soilsync.shop/app/Services/WooCommerceApiService.php"
    if [ -f "$service" ]; then
        echo "  → Updating WooCommerceApiService..."
        cp "$GOLD_MASTER/admin.soilsync.shop/app/Services/WooCommerceApiService.php" "$service.new"
        echo "     ✓ Service updated (review .new file, then rename)"
        echo "     Changes: Uses metadata['short_description'] instead of truncating main description"
    else
        echo "     ⚠ Service not found"
    fi
}

# Update: Stock Control Disabled for Variable Products
UPDATES[no-stock-variable]="Disable stock control for veg boxes (variable products)"
update_no_stock_variable() {
    echo "Deploying: Variable Product Stock Control Fix"
    echo "  → Already included in WooCommerceApiService update"
    echo "     Changes: Variable products set to 'manage_stock=false, always in stock'"
}

# Update: SKU Conflict Auto-Resolution
UPDATES[sku-conflict-fix]="Automatically link existing products on SKU conflict"
update_sku_conflict_fix() {
    echo "Deploying: SKU Conflict Auto-Resolution"
    echo "  → Already included in WooCommerceApiService update"
    echo "     Changes: findProductBySku() method + auto-link logic in syncProduct()"
}

# Update: User Switching Plugin Dependency
UPDATES[user-switching-dep]="Add User Switching plugin dependency check"
update_user_switching_dep() {
    echo "Deploying: User Switching Dependency Declaration"
    
    # 1. Copy plugin if missing
    local plugin_src="$GOLD_MASTER/httpdocs/wp-content/plugins/user-switching"
    local plugin_dst="$TARGET_SITE/httpdocs/wp-content/plugins/user-switching"
    
    if [ ! -d "$plugin_dst" ]; then
        echo "  → Copying User Switching plugin..."
        cp -r "$plugin_src" "$plugin_dst"
        echo "     ✓ Plugin copied - activate in WordPress admin"
    else
        echo "     ✓ Plugin already present"
    fi
    
    # 2. Update mwf-integration plugin header
    local mwf_plugin="$TARGET_SITE/httpdocs/wp-content/plugins/mwf-integration/mwf-integration.php"
    if [ -f "$mwf_plugin" ]; then
        echo "  → Updating mwf-integration plugin..."
        cp "$GOLD_MASTER/httpdocs/wp-content/plugins/mwf-integration/mwf-integration.php" "$mwf_plugin.new"
        echo "     ✓ Plugin updated (review .new file, then rename)"
        echo "     Changes: Added 'Requires Plugins' header + dependency warnings"
    fi
}

# Update: Category Sync Fix
UPDATES[category-sync]="Fix category sync to WooCommerce"
update_category_sync() {
    echo "Deploying: Category Sync Fix"
    echo "  → Already included in WooCommerceApiService update"
    echo "     Changes: Looks up category ID from WordPress DB and sends to WooCommerce API"
}

# Update: WooCommerce Variations CSS Fix
UPDATES[variation-css]="[THEME-SPECIFIC] Fix dropdown alignment (twentytwentyfive theme only)"
update_variation_css() {
    echo "Deploying: Variation Dropdown CSS Fix"
    echo -e "  ${YELLOW}⚠ Note: This fix is for twentytwentyfive theme only${NC}"
    echo -e "  ${YELLOW}  Not needed for Divi or other themes${NC}"
    
    local css_file="$TARGET_SITE/httpdocs/wp-content/themes/middleworld-farms/css/woocommerce-variations.css"
    local css_src="$GOLD_MASTER/httpdocs/wp-content/themes/middleworld-farms/css/woocommerce-variations.css"
    
    # Check if middleworld-farms theme exists
    local theme_dir="$TARGET_SITE/httpdocs/wp-content/themes/middleworld-farms"
    if [ ! -d "$theme_dir" ]; then
        echo -e "  ${RED}✗ Theme not found - skipping${NC}"
        echo "    Target site uses different theme (Divi?)"
        return
    fi
    
    if [ -f "$css_src" ]; then
        echo "  → Updating woocommerce-variations.css..."
        mkdir -p "$(dirname "$css_file")"
        cp "$css_src" "$css_file"
        echo "     ✓ CSS updated"
        echo "     Changes: min-width on labels, flex:1 on selects for alignment"
    fi
}

###############################################################################
# List available updates
###############################################################################
list_updates() {
    echo -e "${BLUE}Available Updates:${NC}\n"
    
    local i=1
    for key in "${!UPDATES[@]}"; do
        echo -e "${GREEN}$i.${NC} ${YELLOW}$key${NC}"
        echo "   ${UPDATES[$key]}"
        echo ""
        ((i++))
    done
    
    echo "Deploy specific: ./deploy-updates.sh --update bulk-sync"
    echo "Deploy all:      ./deploy-updates.sh --update all"
}

###############################################################################
# Deploy specific update
###############################################################################
deploy_update() {
    local update_key="$1"
    
    if [ -z "${UPDATES[$update_key]}" ]; then
        echo -e "${RED}Error: Unknown update '$update_key'${NC}"
        echo "Run with --list to see available updates"
        exit 1
    fi
    
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Deploying Update: $update_key${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
    
    echo "Source: $GOLD_MASTER"
    echo "Target: $TARGET_SITE"
    echo ""
    
    # Call the update function
    "update_${update_key//-/_}"
    
    echo ""
    echo -e "${GREEN}✓ Update deployed${NC}"
    echo ""
    echo -e "${YELLOW}Important:${NC}"
    echo "  1. Review all .new files before replacing originals"
    echo "  2. Test in staging/development first"
    echo "  3. Backup before applying to production"
    echo "  4. Run: php artisan config:clear after Laravel changes"
    echo "  5. Clear WordPress cache after plugin/theme changes"
}

###############################################################################
# Deploy all updates
###############################################################################
deploy_all() {
    echo -e "${BLUE}Deploying ALL updates...${NC}\n"
    
    for key in "${!UPDATES[@]}"; do
        deploy_update "$key"
        echo ""
    done
    
    echo -e "${GREEN}✓ All updates deployed${NC}"
}

###############################################################################
# Main
###############################################################################

if [ "$LIST_ONLY" = true ]; then
    list_updates
    exit 0
fi

if [ -z "$UPDATE_NAME" ]; then
    echo "Error: No update specified"
    echo "Run with --list to see available updates"
    exit 1
fi

# Check target exists
if [ ! -d "$TARGET_SITE" ]; then
    echo -e "${RED}Error: Target site not found: $TARGET_SITE${NC}"
    exit 1
fi

# Confirm deployment
echo -e "${YELLOW}⚠ About to deploy updates to: $TARGET_SITE${NC}"
read -p "Continue? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Aborted."
    exit 1
fi

if [ "$UPDATE_NAME" = "all" ]; then
    deploy_all
else
    deploy_update "$UPDATE_NAME"
fi
