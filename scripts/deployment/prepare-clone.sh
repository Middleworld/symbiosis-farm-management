#!/bin/bash

###############################################################################
# Gold Master Clone Preparation Script
# 
# This script identifies all hardcoded references to soilsync.shop that need
# to be replaced when cloning to a new customer domain.
#
# Usage:
#   ./prepare-clone.sh                    # Dry run - show what needs changing
#   ./prepare-clone.sh --domain new.com   # Show with replacement preview
#   ./prepare-clone.sh --apply new.com    # Actually perform replacements
###############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Current demo domain
OLD_DOMAIN="soilsync.shop"
OLD_ADMIN="admin.soilsync.shop"
OLD_FARMOS="farmos.soilsync.shop"

# New domain (from command line)
NEW_DOMAIN=""
APPLY_CHANGES=false

###############################################################################
# Parse arguments
###############################################################################
while [[ $# -gt 0 ]]; do
    case $1 in
        --domain)
            NEW_DOMAIN="$2"
            shift 2
            ;;
        --apply)
            APPLY_CHANGES=true
            NEW_DOMAIN="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [--domain NEW_DOMAIN] [--apply NEW_DOMAIN]"
            echo ""
            echo "Options:"
            echo "  --domain NEW_DOMAIN    Preview what would change for new domain"
            echo "  --apply NEW_DOMAIN     Actually apply changes (CAUTION!)"
            echo "  --help                 Show this help message"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

###############################################################################
# Functions
###############################################################################

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
}

print_section() {
    echo -e "${YELLOW}▶ $1${NC}"
}

print_file() {
    echo -e "  ${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "  ${RED}⚠${NC} $1"
}

check_file_exists() {
    local file="$1"
    if [ -f "$file" ]; then
        return 0
    else
        return 1
    fi
}

count_occurrences() {
    local file="$1"
    local pattern="$2"
    if [ -f "$file" ]; then
        grep -c "$pattern" "$file" 2>/dev/null || echo "0"
    else
        echo "0"
    fi
}

###############################################################################
# Main Checks
###############################################################################

print_header "GOLD MASTER CLONE PREPARATION"

if [ -n "$NEW_DOMAIN" ]; then
    echo -e "New Domain: ${GREEN}$NEW_DOMAIN${NC}"
    NEW_ADMIN="admin.$NEW_DOMAIN"
    NEW_FARMOS="farmos.$NEW_DOMAIN"
    echo -e "Admin URL:  ${GREEN}$NEW_ADMIN${NC}"
    echo -e "FarmOS URL: ${GREEN}$NEW_FARMOS${NC}"
    echo ""
fi

if [ "$APPLY_CHANGES" = true ]; then
    echo -e "${RED}⚠ WARNING: APPLY MODE - Changes will be made!${NC}\n"
    read -p "Are you sure you want to continue? (yes/no): " confirm
    if [ "$confirm" != "yes" ]; then
        echo "Aborted."
        exit 1
    fi
fi

###############################################################################
# 1. Environment Files
###############################################################################
print_section "1. Environment Files (.env)"

ENV_FILES=(
    "$ROOT_DIR/admin.soilsync.shop/.env"
    "$ROOT_DIR/farmos.soilsync.shop/.env"
    "$ROOT_DIR/httpdocs/wp-config.php"
)

for file in "${ENV_FILES[@]}"; do
    if check_file_exists "$file"; then
        count=$(count_occurrences "$file" "$OLD_DOMAIN")
        print_file "$(basename $file) - $count occurrences of '$OLD_DOMAIN'"
        
        if [ -n "$NEW_DOMAIN" ] && [ "$count" -gt 0 ]; then
            echo "     Preview: soilsync.shop → $NEW_DOMAIN"
            
            if [ "$APPLY_CHANGES" = true ]; then
                # Backup first
                cp "$file" "$file.backup-$(date +%Y%m%d-%H%M%S)"
                # Replace domain
                sed -i "s/$OLD_DOMAIN/$NEW_DOMAIN/g" "$file"
                sed -i "s/$OLD_ADMIN/$NEW_ADMIN/g" "$file"
                sed -i "s/$OLD_FARMOS/$NEW_FARMOS/g" "$file"
                echo "     ${GREEN}✓ Updated${NC}"
            fi
        fi
    else
        print_warning "File not found: $file"
    fi
done

###############################################################################
# 2. Database Names
###############################################################################
print_section "2. Database Configuration"

DB_CONFIGS=(
    "Laravel: admin_demo → admin_[CUSTOMER]"
    "WordPress: wp_demo → wp_[CUSTOMER]"
    "FarmOS: soilsync-user_ → [CUSTOMER]-user_"
)

for config in "${DB_CONFIGS[@]}"; do
    print_file "$config"
done

echo ""
print_warning "Database names must be updated manually in:"
echo "           - .env files (DB_DATABASE variables)"
echo "           - wp-config.php (DB_NAME)"
echo "           - MySQL/MariaDB (CREATE DATABASE + GRANT)"

###############################################################################
# 3. WordPress Site URL
###############################################################################
print_section "3. WordPress Database URLs"

echo "  After importing database, run:"
echo "     ${GREEN}wp search-replace 'soilsync.shop' '$NEW_DOMAIN' --all-tables${NC}"
echo "     ${GREEN}wp search-replace 'admin.soilsync.shop' '$NEW_ADMIN' --all-tables${NC}"

###############################################################################
# 4. Required Plugins
###############################################################################
print_section "4. Required WordPress Plugins"

REQUIRED_PLUGINS=(
    "woocommerce"
    "user-switching"
    "mwf-integration"
    "mwf-solidarity-pricing"
    "mwf-sso"
    "mwf-subscriptions"
    "mwf-team-members"
    "mwf-reviews"
)

for plugin in "${REQUIRED_PLUGINS[@]}"; do
    plugin_path="$ROOT_DIR/httpdocs/wp-content/plugins/$plugin"
    if [ -d "$plugin_path" ]; then
        print_file "$plugin (present)"
    else
        print_warning "$plugin (MISSING)"
    fi
done

###############################################################################
# 5. File Sizes and Exclusions
###############################################################################
print_section "5. Clone Size Analysis"

if [ -d "$ROOT_DIR/admin.soilsync.shop" ]; then
    ADMIN_SIZE=$(du -sh "$ROOT_DIR/admin.soilsync.shop" 2>/dev/null | cut -f1)
    VENDOR_SIZE=$(du -sh "$ROOT_DIR/admin.soilsync.shop/vendor" 2>/dev/null | cut -f1)
    NODE_SIZE=$(du -sh "$ROOT_DIR/admin.soilsync.shop/node_modules" 2>/dev/null | cut -f1)
    
    echo "  Total Laravel:    $ADMIN_SIZE"
    echo "  ├─ vendor/:       $VENDOR_SIZE (run 'composer install' after clone)"
    echo "  └─ node_modules/: $NODE_SIZE (run 'npm install' after clone)"
fi

echo ""
echo "  ${YELLOW}Exclude from clone:${NC}"
echo "     - admin.soilsync.shop/vendor/*"
echo "     - admin.soilsync.shop/node_modules/*"
echo "     - admin.soilsync.shop/storage/logs/*"
echo "     - */cache/*"

###############################################################################
# 6. Summary
###############################################################################
print_header "CLONE CHECKLIST"

echo "Pre-Clone (on Gold Master):"
echo "  [ ] Export MySQL databases (admin_demo, wp_demo, farmos)"
echo "  [ ] Tar Laravel app (exclude vendor/, node_modules/)"
echo "  [ ] Tar WordPress site"
echo "  [ ] Tar farmOS site"
echo "  [ ] Copy deployment scripts"
echo ""
echo "Post-Clone (on New Server):"
echo "  [ ] Create databases with new names"
echo "  [ ] Import database dumps"
echo "  [ ] Extract tarballs to new paths"
echo "  [ ] Update .env files with new domain/credentials"
echo "  [ ] Update wp-config.php with new database"
echo "  [ ] Run: composer install (in Laravel)"
echo "  [ ] Run: npm install (in Laravel)"
echo "  [ ] Run: php artisan key:generate"
echo "  [ ] Run: php artisan config:clear"
echo "  [ ] Run: wp search-replace (old domain → new domain)"
echo "  [ ] Update DNS/SSL certificates"
echo "  [ ] Test all integrations"
echo ""

if [ "$APPLY_CHANGES" = true ]; then
    print_header "CHANGES APPLIED"
    echo -e "${GREEN}Domain references updated. Check backup files.${NC}"
else
    echo -e "${BLUE}This was a dry run. Use --apply to make changes.${NC}"
fi

echo ""
