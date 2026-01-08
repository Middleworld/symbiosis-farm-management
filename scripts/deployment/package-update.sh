#!/bin/bash

###############################################################################
# Package Updates for Distribution
# 
# This script runs on the GOLD MASTER to create update packages
# that customer sites can download and apply.
#
# Usage:
#   ./package-update.sh --create bulk-sync "Bulk sync feature"
#   ./package-update.sh --list
#   ./package-update.sh --publish
###############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GOLD_MASTER="/var/www/vhosts/soilsync.shop"
UPDATES_DIR="$GOLD_MASTER/updates"
PACKAGES_DIR="$UPDATES_DIR/packages"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

mkdir -p "$UPDATES_DIR" "$PACKAGES_DIR"

###############################################################################
# Create update package
###############################################################################
create_package() {
    local update_id="$1"
    local description="$2"
    
    if [ -z "$update_id" ] || [ -z "$description" ]; then
        echo "Usage: $0 --create [update-id] [description]"
        return 1
    fi
    
    echo -e "${BLUE}Creating update package: $update_id${NC}\n"
    
    local package_dir="$PACKAGES_DIR/$update_id"
    mkdir -p "$package_dir"
    
    # Define files to include based on update type
    case "$update_id" in
        bulk-sync)
            copy_files "$package_dir" \
                "admin.soilsync.shop/app/Http/Controllers/Admin/ProductController.php" \
                "admin.soilsync.shop/resources/views/admin/products/index.blade.php"
            ;;
            
        short-description|category-sync|sku-conflict-fix|no-stock-variable)
            copy_files "$package_dir" \
                "admin.soilsync.shop/app/Services/WooCommerceApiService.php"
            ;;
            
        user-switching-dep)
            copy_files "$package_dir" \
                "httpdocs/wp-content/plugins/mwf-integration/mwf-integration.php" \
                "httpdocs/wp-content/plugins/user-switching/"
            ;;
            
        variation-css)
            copy_files "$package_dir" \
                "httpdocs/wp-content/themes/middleworld-farms/css/woocommerce-variations.css"
            ;;
            
        *)
            echo "Unknown update: $update_id"
            return 1
            ;;
    esac
    
    # Create manifest
    create_manifest "$package_dir" "$update_id" "$description"
    
    # Create tarball
    local tarball="$UPDATES_DIR/$update_id.tar.gz"
    tar -czf "$tarball" -C "$package_dir" .
    
    # Generate checksum
    sha256sum "$tarball" | cut -d' ' -f1 > "$UPDATES_DIR/$update_id.sha256"
    
    echo -e "${GREEN}✓ Package created${NC}"
    echo "  Package: $tarball"
    echo "  Size: $(du -h "$tarball" | cut -f1)"
    echo ""
    
    # Cleanup temp dir
    rm -rf "$package_dir"
}

###############################################################################
# Copy files to package directory
###############################################################################
copy_files() {
    local dest_dir="$1"
    shift
    
    for file in "$@"; do
        local src="$GOLD_MASTER/$file"
        local dst="$dest_dir/$file"
        
        if [ -d "$src" ]; then
            # Copy directory
            mkdir -p "$dst"
            cp -r "$src"/* "$dst/"
            echo "  → Added directory: $file"
        elif [ -f "$src" ]; then
            # Copy file
            mkdir -p "$(dirname "$dst")"
            cp "$src" "$dst"
            echo "  → Added file: $file"
        else
            echo "  ⚠ Not found: $file"
        fi
    done
}

###############################################################################
# Create manifest.json
###############################################################################
create_manifest() {
    local package_dir="$1"
    local update_id="$2"
    local description="$3"
    
    local version="1.$(date +%Y%m%d).$(date +%H%M)"
    local date=$(date -I)
    
    # Find all files in package
    local files=$(cd "$package_dir" && find . -type f | sed 's|^\./||' | grep -v "manifest.json")
    
    # Create JSON array of files
    local files_json=$(echo "$files" | jq -R . | jq -s .)
    
    # Determine if laravel or wordpress
    local has_laravel="false"
    local has_wordpress="false"
    
    if echo "$files" | grep -q "admin.soilsync.shop"; then
        has_laravel="true"
    fi
    
    if echo "$files" | grep -q "httpdocs"; then
        has_wordpress="true"
    fi
    
    cat > "$package_dir/manifest.json" <<EOF
{
  "id": "$update_id",
  "version": "$version",
  "date": "$date",
  "name": "$(echo $update_id | tr '-' ' ' | sed 's/\b\(.\)/\u\1/g')",
  "description": "$description",
  "files": $files_json,
  "laravel": $has_laravel,
  "wordpress": $has_wordpress,
  "post_update": {
    "laravel": ["config:clear", "cache:clear"],
    "wordpress": ["cache flush"]
  }
}
EOF
    
    echo "  → Created manifest (version $version)"
}

###############################################################################
# List available packages
###############################################################################
list_packages() {
    echo -e "${BLUE}Available Update Packages:${NC}\n"
    
    if [ ! -d "$UPDATES_DIR" ] || [ -z "$(ls -A "$UPDATES_DIR"/*.tar.gz 2>/dev/null)" ]; then
        echo "No packages created yet"
        return
    fi
    
    for package in "$UPDATES_DIR"/*.tar.gz; do
        local basename=$(basename "$package" .tar.gz)
        local size=$(du -h "$package" | cut -f1)
        local date=$(stat -c %y "$package" | cut -d' ' -f1)
        
        echo "[$basename]"
        echo "  Size: $size"
        echo "  Created: $date"
        
        if [ -f "$UPDATES_DIR/$basename.sha256" ]; then
            local checksum=$(cat "$UPDATES_DIR/$basename.sha256")
            echo "  SHA256: $checksum"
        fi
        
        echo ""
    done
}

###############################################################################
# Publish updates (create master manifest)
###############################################################################
publish_updates() {
    echo -e "${BLUE}Publishing updates...${NC}\n"
    
    # Create master manifest listing all available updates
    local updates_json="[]"
    
    for package in "$UPDATES_DIR"/*.tar.gz; do
        if [ ! -f "$package" ]; then
            continue
        fi
        
        local basename=$(basename "$package" .tar.gz)
        local extract_dir="$UPDATES_DIR/.extract-$basename"
        
        mkdir -p "$extract_dir"
        tar -xzf "$package" -C "$extract_dir"
        
        if [ -f "$extract_dir/manifest.json" ]; then
            local manifest=$(cat "$extract_dir/manifest.json")
            updates_json=$(echo "$updates_json" | jq --argjson update "$manifest" '. += [$update]')
        fi
        
        rm -rf "$extract_dir"
    done
    
    # Create master manifest
    cat > "$UPDATES_DIR/manifest.json" <<EOF
{
  "version": "1.0",
  "last_updated": "$(date -I)",
  "updates": $updates_json
}
EOF
    
    echo -e "${GREEN}✓ Master manifest created${NC}"
    echo "  Location: $UPDATES_DIR/manifest.json"
    echo ""
    echo "Customer sites can now check for updates"
}

###############################################################################
# Main
###############################################################################

case "${1:-}" in
    --create)
        create_package "$2" "$3"
        ;;
    --list)
        list_packages
        ;;
    --publish)
        publish_updates
        ;;
    --help|*)
        echo "Usage: $0 [COMMAND]"
        echo ""
        echo "Commands:"
        echo "  --create ID DESC    Create update package"
        echo "  --list              List available packages"
        echo "  --publish           Publish master manifest"
        echo "  --help              Show this help"
        echo ""
        echo "Available update IDs:"
        echo "  bulk-sync, short-description, category-sync,"
        echo "  sku-conflict-fix, no-stock-variable, user-switching-dep,"
        echo "  variation-css"
        ;;
esac
