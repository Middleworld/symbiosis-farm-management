#!/bin/bash
# Documentation Reorganization Script
# Moves documentation files into proper user-manual / developer / internal structure

REPO_ROOT="/var/www/vhosts/soilsync.shop/admin.soilsync.shop"
cd "$REPO_ROOT"

echo "============================================"
echo "   Reorganizing Documentation"
echo "============================================"
echo ""

# Create directory structure
mkdir -p docs/user-manual
mkdir -p docs/developer/api
mkdir -p docs/developer/integrations
mkdir -p docs/deployment
mkdir -p docs/internal

echo "## Moving Files to User Manual"
# User-facing documentation
declare -A USER_MANUAL_MOVES=(
    ["docs/SUCCESSION_PLANNER_README.md"]="docs/user-manual/SUCCESSION_PLANNING.md"
    ["docs/TASK_SYSTEM_GUIDE.md"]="docs/user-manual/TASK_SYSTEM.md"
    ["docs/ADMIN_USER_MANAGEMENT.md"]="docs/user-manual/USER_MANAGEMENT.md"
    ["docs/CRM-READY.md"]="docs/user-manual/CRM_USAGE.md"
    ["docs/POS-HARDWARE-INTEGRATION.md"]="docs/user-manual/POS_INTEGRATION.md"
)

for src in "${!USER_MANUAL_MOVES[@]}"; do
    dest="${USER_MANUAL_MOVES[$src]}"
    if [ -f "$src" ]; then
        echo "  $src → $dest"
        mv "$src" "$dest"
    fi
done
echo ""

echo "## Moving Files to Developer Documentation"
# Developer documentation
declare -A DEVELOPER_MOVES=(
    ["LARAVEL-API-SETUP.md"]="docs/developer/api/LARAVEL_API.md"
    ["docs/laravel-admin-endpoints.md"]="docs/developer/api/ENDPOINTS.md"
    ["CLAUDE-API-SETUP.md"]="docs/developer/integrations/CLAUDE_AI.md"
    ["docs/FARMOS_OAUTH_SETUP.md"]="docs/developer/integrations/FARMOS_AUTH.md"
    ["docs/FARMOS_PAGE_API_INTEGRATION_GUIDE.md"]="docs/developer/integrations/FARMOS_API.md"
    ["docs/FARMOS_PLANT_TYPE_COMPLETE_SETUP.md"]="docs/developer/integrations/FARMOS_PLANT_TYPES.md"
    ["docs/FARMOS_SPACING_FIELDS_SETUP.md"]="docs/developer/integrations/FARMOS_SPACING.md"
    ["docs/3CX-CRM-INTEGRATION.md"]="docs/developer/integrations/3CX_CRM.md"
    ["docs/WEATHER_RAG_INTEGRATION.md"]="docs/developer/integrations/WEATHER_RAG.md"
    ["STRIPE_SETUP.md"]="docs/developer/integrations/STRIPE.md"
    ["docs/SPACING_AUTO_POPULATION_FEATURE.md"]="docs/developer/SPACING_FEATURE.md"
    ["docs/configuration.md"]="docs/developer/CONFIGURATION.md"
)

for src in "${!DEVELOPER_MOVES[@]}"; do
    dest="${DEVELOPER_MOVES[$src]}"
    if [ -f "$src" ]; then
        echo "  $src → $dest"
        mv "$src" "$dest"
    fi
done
echo ""

echo "## Moving Files to Deployment Documentation"
# Deployment documentation
declare -A DEPLOYMENT_MOVES=(
    ["DEVELOPMENT_SETUP.md"]="docs/deployment/DEVELOPMENT.md"
    ["scripts/deployment/README.md"]="docs/deployment/SCRIPTS.md"
)

for src in "${!DEPLOYMENT_MOVES[@]}"; do
    dest="${DEPLOYMENT_MOVES[$src]}"
    if [ -f "$src" ]; then
        echo "  $src → $dest"
        mv "$src" "$dest"
    fi
done
echo ""

echo "## Moving Files to Internal Documentation"
# Internal/implementation documentation
INTERNAL_FILES=(
    "GROK_SSL_DAMAGE_REPORT_2025-12-20.md"
    "ARCHITECTURE-CLARIFICATION-FOR-ADMIN-TEAM.md"
    "INTEGRATION-STATUS-UPDATE.md"
    "SUBSCRIPTION_ARCHITECTURE_STATUS.md"
    "SUBSCRIPTION_REPLACEMENT_STATUS.md"
    "WOOCOMMERCE_SUBSCRIPTION_MIGRATION.md"
    "WCS-REMOVAL-READINESS-ASSESSMENT.md"
    "API-FORMAT-MISMATCH-FOUND.md"
    "API-FIXED-WORDPRESS-FORMAT.md"
    "VEGBOX_SUBSCRIPTION_PROJECT_PLAN.md"
    "VEGBOX_SUBSCRIPTION_COMPLETION_SUMMARY.md"
    "VEGBOX_QUICK_REFERENCE.md"
    "VEGBOX_DOCUMENTATION_INDEX.md"
    "MWF_SUBSCRIPTIONS_IMPLEMENTATION.md"
    "SUBSCRIPTION-UPGRADE-DOWNGRADE-IMPLEMENTATION.md"
    "PLAN-CHANGE-IMPLEMENTATION-COMPLETE.md"
    "GRACE_PERIOD_IMPLEMENTATION.md"
    "RENEWAL-ORDER-CREATION.md"
    "COLLECTION_DAY_SYSTEM.md"
    "DELIVERY_SCHEDULE_FIX.md"
    "ORDER_PAGE_ENHANCEMENTS.md"
    "WOO_DEPENDENCY_TEST_PLAN.md"
    "MIGRATION-API-ENDPOINT.md"
    "CHATBOT_PHI3_SETUP.md"
    "CHATBOT_TIMEOUT_FIX.md"
    "DUAL-OLLAMA-SETUP.md"
    "OLLAMA_PORT_CONFIGURATION.md"
    "MISTRAL-7B-ACCURACY-TEST.md"
    "AI_TIMING_ADJUSTMENTS.md"
    "AI_RECOMMENDATION_PARSER.md"
    "RAG_SYSTEM_SUMMARY.md"
    "RAG_UPLOAD_FIX.md"
    "RAG_UPLOAD_TROUBLESHOOTING.md"
    "DATASET_IMPORT_UI.md"
    "ATTRIBUTES-VARIATIONS-STRATEGY.md"
    "HARVEST-METHOD-CLEANUP-SUMMARY.md"
    "VARIETY-AUDIT-GUIDE.md"
    "AUDIT-QUICK-REF.md"
    "AUDIT-PAUSE-RESUME.md"
    "CROP_PLANNING_IMPROVEMENTS.md"
    "STRIPE_ORDER_TRACKING.md"
    "package_accounts_instructions.md"
    "Subscriptions.md"
)

for file in "${INTERNAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  $file → docs/internal/$file"
        mv "$file" "docs/internal/"
    fi
done
echo ""

echo "## Preserving Important Root Documentation"
echo "Keeping in root:"
echo "  - README.md (main project readme)"
echo "  - CONTRIBUTING.md (contribution guidelines)"
echo "  - OPEN_SOURCE_CHECKLIST.md (release checklist)"
echo ""

echo "============================================"
echo "   Reorganization Complete"
echo "============================================"
echo ""
echo "Documentation structure:"
echo "  docs/"
echo "    ├── README.md (index)"
echo "    ├── user-manual/ (end-user docs)"
echo "    ├── developer/ (API, integrations)"
echo "    ├── deployment/ (installation, setup)"
echo "    └── internal/ (implementation notes)"
echo ""
echo "Next steps:"
echo "1. Review file locations: ls -R docs/"
echo "2. Update internal cross-references in moved files"
echo "3. Test documentation links"
echo "4. Commit: git add . && git commit -m 'docs: reorganize into user/dev/internal'"
echo ""
