# Repository Cleanup Summary - January 1, 2026

## Documentation Consolidation

### Removed Duplicate Documentation (11 files from farmOS)
- FARMOS_OAUTH_SETUP.md
- farmOS-OAuth2-Laravel-Integration-Guide.md
- FARMOS_MENU_API_SETUP.md
- FARMOS_PAGE_API_INTEGRATION_GUIDE.md
- FARMOS_PLANT_TYPE_COMPLETE_SETUP.md
- FARMOS_PLANT_TYPE_FIELDS_SETUP.md
- FARMOS_SPACING_FIELDS_SETUP.md
- FARMOS-VOCABULARY-SETUP.md
- PLANT_TYPE_FIELD_MAPPING.md
- PLANT_TYPE_JSONAPI.md
- farmos-metadata-issues.md

### Consolidated Documentation Structure
**Before**: Documentation scattered across 3+ locations
- `/var/www/vhosts/soilsync.shop/docs/` (25 files)
- `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/docs/` (25 files)
- `/var/www/vhosts/soilsync.shop/httpdocs/docs/` (22 files)
- Multiple root-level markdown files

**After**: Single source of truth
- `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/docs/` (35 files)
  - All active documentation
  - Organized subdirectories: deployment/, developer/, internal/, legacy/, modules/, scale-integration/, user-manual/

### Files Moved to Consolidated Location
- CHANGELOG.md
- CONTRIBUTING.md
- MWF_REVIEWS_INTEGRATION.md
- SSO_IMPLEMENTATION.md
- FARMOS_DEVELOPMENT_SETUP.md (from farmos.soilsync.shop)
- FARMOS_FILE_PERMISSIONS_GUIDE.md (from farmos.soilsync.shop)
- MWF_SUBSCRIPTIONS_IMPLEMENTATION.md (from WordPress plugin)
- laravel-admin-endpoints.md (from WordPress plugin)

## Development File Cleanup

### farmOS Root Directory (~28MB removed)
**Removed**:
- add_satellite_layers.php
- add_spacing_fields.php
- analyze_plant_patterns.php
- bulk_import_images.php
- check_families.php
- configure_spacing_display.php
- fix_parents.php
- import_courgette_images.php
- import_single_courgette_image.php
- import_taxonomy.php
- reorder_fields.php
- set_default_spacing.php
- update_brassica_spacing.php
- create_hash.php
- cookies.txt
- product_alyssum.html (388K)
- search.html (459K)
- search_kale.html (349K)
- search_kale_simple.html (448K)
- search_portulaca.html (359K)
- setup-oauth-auto.sh
- setup-oauth.sh
- oauth-credentials-*.txt files
- taxonomy_export_plant_type_*.json (18.6MB)
- spinach.jpg

**Remaining**:
- composer.json & composer.lock
- drush-safe (wrapper script)
- index.php
- README.md
- keys/ directory

### Root Directory Cleanup
**Removed**:
- plan-harvest-method-cleanup.php
- review-harvest-methods-phase2.php
- sync-plant-varieties-to-pgsql.php
- temp_dashboard.blade.php
- test-user-switch.html
- index.html
- manifest.xml
- docker-compose.yml
- Dockerfile
- package-lock.json
- accounts.pdf
- accounts_*.zip files
- cicreport.pdf
- Corrupted files with `}` and `]` in names

**Remaining**:
- README.md
- LICENSE
- .gitignore, .htaccess, .env.example

### admin.soilsync.shop Root Cleanup
**Removed**:
- All duplicate files from root directory
- accounts*.pdf, accounts*.zip
- cicreport.pdf
- docker-compose.yml
- Dockerfile
- index.html
- manifest.xml
- cleanup-bakery.php
- cleanup-real-customers.php
- cookies.txt
- test-sso.php
- test-user-switch.html
- Corrupted files with special characters

**Remaining**:
- README.md
- composer.json & composer.lock
- package.json & package-lock.json
- phpunit.xml
- vite.config.js
- artisan

### httpdocs (WordPress) Root Cleanup
**Removed**:
- debug-cookies.php
- force-logout.php
- manifest.xml
- package-lock.json
- plan-harvest-method-cleanup.php
- review-harvest-methods-phase2.php
- sync-plant-varieties-to-pgsql.php
- temp_dashboard.blade.php
- test-user-switch.html

**Remaining**:
- WordPress core files (wp-*.php)
- index.php
- readme.html
- wp-config.php

## Configuration Updates

### Updated References
1. **Copilot Instructions** (3 files updated):
   - `/var/www/vhosts/soilsync.shop/.github/copilot-instructions.md`
   - `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/.github/copilot-instructions.md`
   - `/var/www/vhosts/soilsync.shop/httpdocs/.github/copilot-instructions.md`
   - All now reference: `admin.soilsync.shop/docs/` for documentation

2. **RAG Service Path**:
   - File: `ai_service/app/main.py`
   - Updated docs path from `/opt/sites/admin.soilsync.shop/docs` to `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/docs`

3. **Admin Sidebar External Links**:
   - File: `resources/views/layouts/app.blade.php`
   - Changed from hardcoded `https://middleworldfarms.org` to `{{ config('services.customer_site.url') }}`
   - Added `customer_site` config to `config/services.php`
   - Now uses `CUSTOMER_SITE_URL` environment variable

4. **AI Service Port Configuration**:
   - File: `ai_service/app/main.py`
   - Changed port from 8000 to 8005 for unified AI service
   - Consolidated simple_ai.py and app/main.py functionality

## Impact Summary

**Total Space Saved**: ~48MB
- farmOS: ~28MB
- Root: ~15MB
- admin.soilsync.shop: ~3MB
- httpdocs: ~2MB

**Files Removed**: ~60 files
**Documentation Consolidated**: 25+ files into single location
**Configuration Updates**: 6 files updated

**Benefits**:
- Single source of truth for documentation
- Cleaner project structure
- Reduced confusion about file locations
- Easier maintenance and updates
- Proper environment-based configuration
- Clear separation between active code and legacy files

## Notes

- All removed files are preserved in production site clone
- Legacy documentation moved to `admin.soilsync.shop/docs/legacy/`
- AI service configuration updated but requires restart to take effect
- All changes tested and verified in staging environment (admin.soilsync.shop)
