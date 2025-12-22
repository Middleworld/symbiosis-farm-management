#!/bin/bash
# Documentation Audit and Sanitization Script
# Identifies documentation files with hardcoded URLs that need to be made generic

REPO_ROOT="/var/www/vhosts/soilsync.shop/admin.soilsync.shop"
cd "$REPO_ROOT"

echo "============================================"
echo "   Documentation Audit Report"
echo "   Generated: $(date)"
echo "============================================"
echo ""

# URLs to find and replace
PRODUCTION_URLS=(
    "admin.middleworldfarms.org:8444"
    "admin.middleworldfarms.org"
    "admin.soilsync.shop:8445"
    "admin.soilsync.shop"
    "middleworldfarms.org"
    "soilsync.shop"
)

# Categorize documentation files
echo "## Documentation Files by Category"
echo ""

echo "### User-Facing Documentation (needs generic URLs)"
USER_DOCS=(
    "README.md"
    "CONTRIBUTING.md"
    "docs/SUCCESSION_PLANNER_README.md"
    "docs/TASK_SYSTEM_GUIDE.md"
    "docs/ADMIN_USER_MANAGEMENT.md"
    "docs/CRM-READY.md"
    "docs/3CX-CRM-INTEGRATION.md"
    "docs/POS-HARDWARE-INTEGRATION.md"
    "STRIPE_SETUP.md"
)

for doc in "${USER_DOCS[@]}"; do
    if [ -f "$doc" ]; then
        count=0
        for url in "${PRODUCTION_URLS[@]}"; do
            matches=$(grep -c "$url" "$doc" 2>/dev/null || echo "0")
            count=$((count + matches))
        done
        if [ "$count" -gt 0 ]; then
            echo "  ⚠️  $doc - $count URL references"
        else
            echo "  ✅ $doc - clean"
        fi
    fi
done
echo ""

echo "### Developer Documentation (needs generic URLs)"
DEV_DOCS=(
    "docs/FARMOS_OAUTH_SETUP.md"
    "docs/FARMOS_PAGE_API_INTEGRATION_GUIDE.md"
    "docs/FARMOS_PLANT_TYPE_COMPLETE_SETUP.md"
    "docs/laravel-admin-endpoints.md"
    "docs/configuration.md"
    "LARAVEL-API-SETUP.md"
    "CLAUDE-API-SETUP.md"
)

for doc in "${DEV_DOCS[@]}"; do
    if [ -f "$doc" ]; then
        count=0
        for url in "${PRODUCTION_URLS[@]}"; do
            matches=$(grep -c "$url" "$doc" 2>/dev/null || echo "0")
            count=$((count + matches))
        done
        if [ "$count" -gt 0 ]; then
            echo "  ⚠️  $doc - $count URL references"
        else
            echo "  ✅ $doc - clean"
        fi
    fi
done
echo ""

echo "### Internal Documentation (can keep specific URLs)"
INTERNAL_DOCS=(
    "GROK_SSL_DAMAGE_REPORT_2025-12-20.md"
    "ARCHITECTURE-CLARIFICATION-FOR-ADMIN-TEAM.md"
    "INTEGRATION-STATUS-UPDATE.md"
    "SUBSCRIPTION_ARCHITECTURE_STATUS.md"
    "WOOCOMMERCE_SUBSCRIPTION_MIGRATION.md"
    "API-FORMAT-MISMATCH-FOUND.md"
    "VEGBOX_SUBSCRIPTION_PROJECT_PLAN.md"
)

for doc in "${INTERNAL_DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo "  📝 $doc - internal (move to docs/internal/)"
    fi
done
echo ""

echo "============================================"
echo "   URL Sanitization Recommendations"
echo "============================================"
echo ""
echo "Replace specific URLs with generic placeholders:"
echo ""
echo "1. Production URLs:"
echo "   ❌ https://admin.middleworldfarms.org:8444"
echo "   ❌ https://admin.middleworldfarms.org"
echo "   ✅ https://your-domain.com"
echo ""
echo "2. API Endpoints:"
echo "   ❌ https://admin.middleworldfarms.org:8444/api/subscriptions"
echo "   ✅ https://your-domain.com/api/subscriptions"
echo ""
echo "3. Code Examples:"
echo "   ❌ \$apiUrl = 'https://admin.middleworldfarms.org:8444/api';"
echo "   ✅ \$apiUrl = config('app.url') . '/api';"
echo ""
echo "4. Environment Variables:"
echo "   ❌ APP_URL=https://admin.middleworldfarms.org:8444"
echo "   ✅ APP_URL=https://your-domain.com"
echo ""

echo "============================================"
echo "   Action Items"
echo "============================================"
echo ""
echo "1. Run: ./scripts/sanitize-documentation-urls.sh"
echo "   This will automatically replace hardcoded URLs"
echo ""
echo "2. Move internal docs to docs/internal/:"
echo "   - GROK_SSL_DAMAGE_REPORT_2025-12-20.md"
echo "   - ARCHITECTURE-CLARIFICATION-FOR-ADMIN-TEAM.md"
echo "   - Various *-STATUS.md and *-IMPLEMENTATION.md files"
echo ""
echo "3. Organize user docs into docs/user-manual/"
echo "4. Organize developer docs into docs/developer/"
echo "5. Review and update main README.md"
echo ""
echo "Run: ./scripts/reorganize-documentation.sh"
echo "To automatically categorize and move files"
echo ""
