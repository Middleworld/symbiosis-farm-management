#!/bin/bash
# URL Sanitization Script for Documentation
# Replaces hardcoded production URLs with generic placeholders for open source

REPO_ROOT="/var/www/vhosts/soilsync.shop/admin.soilsync.shop"
cd "$REPO_ROOT"

# Backup before making changes
BACKUP_DIR="docs-backup-$(date +%Y%m%d-%H%M%S)"
echo "Creating backup in $BACKUP_DIR..."
mkdir -p "$BACKUP_DIR"
find . -name "*.md" ! -path "./vendor/*" ! -path "./node_modules/*" ! -path "./venv/*" -exec cp --parents {} "$BACKUP_DIR" \;

echo "============================================"
echo "   Sanitizing Documentation URLs"
echo "============================================"
echo ""

# Files to sanitize (user-facing and developer documentation)
DOCS_TO_SANITIZE=(
    "README.md"
    "CONTRIBUTING.md"
    "STRIPE_SETUP.md"
    "CLAUDE-API-SETUP.md"
    "docs/SUCCESSION_PLANNER_README.md"
    "docs/TASK_SYSTEM_GUIDE.md"
    "docs/ADMIN_USER_MANAGEMENT.md"
    "docs/CRM-READY.md"
    "docs/3CX-CRM-INTEGRATION.md"
    "docs/POS-HARDWARE-INTEGRATION.md"
    "docs/FARMOS_OAUTH_SETUP.md"
    "docs/FARMOS_PAGE_API_INTEGRATION_GUIDE.md"
    "docs/FARMOS_PLANT_TYPE_COMPLETE_SETUP.md"
    "docs/FARMOS_SPACING_FIELDS_SETUP.md"
    "docs/SUCCESSION_PLANNING_COMPLETE_INSTRUCTIONS.md"
    "docs/WEATHER_RAG_INTEGRATION.md"
    "docs/SPACING_AUTO_POPULATION_FEATURE.md"
    "docs/laravel-admin-endpoints.md"
    "docs/configuration.md"
    "docs/MWF-INTEGRATION-README.md"
)

# URL replacements
declare -A URL_REPLACEMENTS=(
    ["https://admin.middleworldfarms.org:8444"]="https://your-domain.com"
    ["https://admin.middleworldfarms.org"]="https://your-domain.com"
    ["http://admin.middleworldfarms.org:8444"]="https://your-domain.com"
    ["http://admin.middleworldfarms.org"]="https://your-domain.com"
    ["admin.middleworldfarms.org:8444"]="your-domain.com"
    ["admin.middleworldfarms.org"]="your-domain.com"
    ["https://admin.soilsync.shop:8445"]="https://your-demo-domain.com"
    ["https://admin.soilsync.shop"]="https://your-demo-domain.com"
    ["admin.soilsync.shop"]="your-demo-domain.com"
)

# Database name replacements
declare -A DB_REPLACEMENTS=(
    ["admin_db"]="your_database"
    ["admin_demo"]="your_demo_database"
    ["wp_demo"]="your_wordpress_database"
)

# API key placeholder
API_KEY_PATTERN="negc0DToZXSTDdZHAhxLzjVJo57GQSri"
API_KEY_REPLACEMENT="your-api-key-here"

for doc in "${DOCS_TO_SANITIZE[@]}"; do
    if [ -f "$doc" ]; then
        echo "Processing: $doc"
        
        # Create temporary file
        temp_file=$(mktemp)
        cp "$doc" "$temp_file"
        
        # Replace URLs
        for old_url in "${!URL_REPLACEMENTS[@]}"; do
            new_url="${URL_REPLACEMENTS[$old_url]}"
            sed -i "s|$old_url|$new_url|g" "$temp_file"
        done
        
        # Replace database names (only in code blocks and config examples)
        for old_db in "${!DB_REPLACEMENTS[@]}"; do
            new_db="${DB_REPLACEMENTS[$old_db]}"
            # Only replace in obvious database context (DB_DATABASE, define, etc.)
            sed -i "s/DB_DATABASE=$old_db/DB_DATABASE=$new_db/g" "$temp_file"
            sed -i "s/'DB_NAME', '$old_db'/'DB_NAME', '$new_db'/g" "$temp_file"
        done
        
        # Replace API keys
        sed -i "s/$API_KEY_PATTERN/$API_KEY_REPLACEMENT/g" "$temp_file"
        
        # Move sanitized file back
        mv "$temp_file" "$doc"
        echo "  ✅ Sanitized"
    else
        echo "  ⚠️  Not found: $doc"
    fi
done

echo ""
echo "============================================"
echo "   URL Sanitization Complete"
echo "============================================"
echo ""
echo "Backup saved to: $BACKUP_DIR"
echo ""
echo "Next steps:"
echo "1. Review changes: git diff"
echo "2. Test documentation readability"
echo "3. Commit changes: git add . && git commit -m 'docs: sanitize URLs for open source'"
echo ""
echo "To restore from backup if needed:"
echo "cp -r $BACKUP_DIR/* ."
echo ""
