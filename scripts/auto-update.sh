#!/bin/bash

###############################################################################
# Auto-Update Client Script
# 
# This script runs on CUSTOMER sites to check for and apply updates
# from the gold master repository.
#
# Installation on customer site:
#   1. Copy this script to /var/www/vhosts/[customer]/scripts/
#   2. Configure UPDATE_SERVER_URL below
#   3. Run: ./auto-update.sh --check
#
# Usage:
#   ./auto-update.sh --check           # Check for available updates
#   ./auto-update.sh --download        # Download pending updates
#   ./auto-update.sh --apply [ID]      # Apply downloaded update
#   ./auto-update.sh --auto            # Check, download, and prompt to apply
###############################################################################

set -e

# Configuration
UPDATE_SERVER_URL="https://updates.soilsync.shop/api/updates"  # Gold master update API
SITE_ROOT="/var/www/vhosts/$(hostname -f)"
UPDATES_DIR="$SITE_ROOT/updates"
CURRENT_VERSION_FILE="$UPDATES_DIR/current-version.txt"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Ensure updates directory exists
mkdir -p "$UPDATES_DIR"

###############################################################################
# Get current installed version
###############################################################################
get_current_version() {
    if [ -f "$CURRENT_VERSION_FILE" ]; then
        cat "$CURRENT_VERSION_FILE"
    else
        echo "1.0.0"  # Default version
    fi
}

###############################################################################
# Check for available updates from gold master
###############################################################################
check_updates() {
    echo -e "${BLUE}Checking for updates...${NC}\n"
    
    local current_version=$(get_current_version)
    echo "Current version: $current_version"
    
    # Call update server API
    local response=$(curl -s "$UPDATE_SERVER_URL/check?version=$current_version" 2>/dev/null || echo '{"error": "Cannot reach update server"}')
    
    if echo "$response" | grep -q "error"; then
        echo -e "${RED}✗ Cannot reach update server${NC}"
        echo "  Trying local gold master..."
        
        # Fallback: Check local gold master if on same server
        if [ -f "/var/www/vhosts/soilsync.shop/updates/manifest.json" ]; then
            response=$(cat /var/www/vhosts/soilsync.shop/updates/manifest.json)
        else
            echo -e "${RED}✗ No updates available${NC}"
            return 1
        fi
    fi
    
    echo "$response" > "$UPDATES_DIR/available-updates.json"
    
    # Parse and display available updates
    local update_count=$(echo "$response" | grep -o '"id"' | wc -l)
    
    if [ "$update_count" -eq 0 ]; then
        echo -e "${GREEN}✓ Your system is up to date${NC}"
    else
        echo -e "${YELLOW}$update_count update(s) available:${NC}\n"
        echo "$response" | jq -r '.updates[] | "  [\(.id)] \(.name)\n      \(.description)\n      Released: \(.date)"' 2>/dev/null || echo "$response"
    fi
}

###############################################################################
# Download update package
###############################################################################
download_update() {
    local update_id="$1"
    
    if [ -z "$update_id" ]; then
        echo "Usage: $0 --download [update-id]"
        echo "Run --check first to see available updates"
        return 1
    fi
    
    echo -e "${BLUE}Downloading update: $update_id${NC}\n"
    
    # Download from update server
    local package_url="$UPDATE_SERVER_URL/download/$update_id"
    local package_file="$UPDATES_DIR/$update_id.tar.gz"
    
    curl -L -o "$package_file" "$package_url" 2>/dev/null || {
        echo -e "${RED}✗ Download failed${NC}"
        
        # Fallback: Copy from local gold master
        if [ -f "/var/www/vhosts/soilsync.shop/updates/$update_id.tar.gz" ]; then
            echo "  Copying from local gold master..."
            cp "/var/www/vhosts/soilsync.shop/updates/$update_id.tar.gz" "$package_file"
        else
            return 1
        fi
    }
    
    # Verify checksum
    local checksum_url="$UPDATE_SERVER_URL/checksum/$update_id"
    local expected_checksum=$(curl -s "$checksum_url" 2>/dev/null)
    local actual_checksum=$(sha256sum "$package_file" | cut -d' ' -f1)
    
    if [ "$expected_checksum" != "$actual_checksum" ]; then
        echo -e "${RED}✗ Checksum mismatch - update may be corrupted${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓ Update downloaded and verified${NC}"
    echo "  Location: $package_file"
    echo ""
    echo "To apply: $0 --apply $update_id"
}

###############################################################################
# Apply downloaded update
###############################################################################
apply_update() {
    local update_id="$1"
    
    if [ -z "$update_id" ]; then
        echo "Usage: $0 --apply [update-id]"
        return 1
    fi
    
    local package_file="$UPDATES_DIR/$update_id.tar.gz"
    
    if [ ! -f "$package_file" ]; then
        echo -e "${RED}✗ Update not downloaded${NC}"
        echo "  Run: $0 --download $update_id"
        return 1
    fi
    
    echo -e "${YELLOW}⚠ About to apply update: $update_id${NC}"
    echo -e "${YELLOW}  This will modify files on your system${NC}"
    read -p "Continue? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        echo "Aborted."
        return 1
    fi
    
    echo -e "${BLUE}Applying update...${NC}\n"
    
    # Create backup
    local backup_dir="$UPDATES_DIR/backups/$(date +%Y%m%d-%H%M%S)-$update_id"
    mkdir -p "$backup_dir"
    echo "  → Creating backup: $backup_dir"
    
    # Extract update package
    local extract_dir="$UPDATES_DIR/extract-$update_id"
    mkdir -p "$extract_dir"
    tar -xzf "$package_file" -C "$extract_dir"
    
    # Read update manifest
    local manifest="$extract_dir/manifest.json"
    if [ ! -f "$manifest" ]; then
        echo -e "${RED}✗ Invalid update package - missing manifest${NC}"
        return 1
    fi
    
    # Apply files according to manifest
    echo "  → Applying files..."
    
    # Example: Copy updated files with backup
    while IFS= read -r file; do
        local src="$extract_dir/$file"
        local dst="$SITE_ROOT/$file"
        
        if [ -f "$dst" ]; then
            # Backup existing file
            mkdir -p "$(dirname "$backup_dir/$file")"
            cp "$dst" "$backup_dir/$file"
        fi
        
        # Copy new file
        mkdir -p "$(dirname "$dst")"
        cp "$src" "$dst"
        echo "     ✓ Updated: $file"
    done < <(jq -r '.files[]' "$manifest")
    
    # Run post-update commands
    echo "  → Running post-update tasks..."
    
    if grep -q "laravel" "$manifest"; then
        echo "     → Clearing Laravel cache..."
        cd "$SITE_ROOT/admin.soilsync.shop" && php artisan config:clear
        cd "$SITE_ROOT/admin.soilsync.shop" && php artisan cache:clear
    fi
    
    if grep -q "wordpress" "$manifest"; then
        echo "     → Clearing WordPress cache..."
        wp cache flush --path="$SITE_ROOT/httpdocs" 2>/dev/null || true
    fi
    
    # Update version file
    local new_version=$(jq -r '.version' "$manifest")
    echo "$new_version" > "$CURRENT_VERSION_FILE"
    
    # Cleanup
    rm -rf "$extract_dir"
    
    echo ""
    echo -e "${GREEN}✓ Update applied successfully${NC}"
    echo "  New version: $new_version"
    echo "  Backup saved: $backup_dir"
    echo ""
    echo "  Review changes and test your site"
}

###############################################################################
# Auto-update: Check, download, prompt to apply
###############################################################################
auto_update() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  Auto-Update System${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
    
    check_updates
    
    # Check if updates are available
    if [ -f "$UPDATES_DIR/available-updates.json" ]; then
        local update_ids=$(cat "$UPDATES_DIR/available-updates.json" | jq -r '.updates[].id' 2>/dev/null)
        
        if [ -n "$update_ids" ]; then
            echo ""
            read -p "Download updates now? (yes/no): " download_confirm
            
            if [ "$download_confirm" = "yes" ]; then
                while IFS= read -r update_id; do
                    download_update "$update_id"
                done <<< "$update_ids"
                
                echo ""
                echo -e "${YELLOW}Updates downloaded.${NC}"
                echo "Review and apply individually with: $0 --apply [update-id]"
            fi
        fi
    fi
}

###############################################################################
# Main
###############################################################################

case "${1:-}" in
    --check)
        check_updates
        ;;
    --download)
        download_update "$2"
        ;;
    --apply)
        apply_update "$2"
        ;;
    --auto)
        auto_update
        ;;
    --help|*)
        echo "Usage: $0 [COMMAND]"
        echo ""
        echo "Commands:"
        echo "  --check           Check for available updates"
        echo "  --download ID     Download specific update"
        echo "  --apply ID        Apply downloaded update"
        echo "  --auto            Check, download, and prompt to apply"
        echo "  --help            Show this help"
        echo ""
        echo "Current version: $(get_current_version)"
        ;;
esac
