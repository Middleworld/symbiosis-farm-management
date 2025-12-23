# Middle World Farms - Farm Delivery System AI Instructions

## Project Overview
Laravel 12 (PHP 8.2+) application for Community Supported Agriculture (CSA) delivery management with deep farmOS integration, multi-database architecture, AI-powered crop planning, and **native subscription management replacing WooCommerce Subscriptions**.

**Essential Knowledge for AI Agents:**
- **Staging-first development**: Always work in `admin.soilsync.shop` (demo branch), never directly in production
- **Performance-critical**: Use local DB mirrors for reads, FarmOS API only for writes/sync
- **Git history first**: Check existing implementations before coding new features
- **Multi-database**: Laravel (mysql), WordPress (wordpress), farmOS (farmos) connections
- **Service architecture**: External integrations via dedicated services, not direct DB queries
- **AI timeouts**: CPU-only processing requires 60-90s minimum timeouts
- **Subscription transition**: Parallel WooCommerce/Vegbox systems during migration

## ⚠️ Critical Environment Warnings
- **LIVE PRODUCTION SITE**: URL `https://admin.middleworldfarms.org:8444/admin/` (port 8444, NOT 8000)
- **DO NOT START DEV SERVERS**: Site is already live via production configuration
- **CPU-ONLY SERVER**: No GPU access - all AI processing on CPU requires 60-90s timeouts minimum
- **ALL FILE CHANGES ARE IMMEDIATELY LIVE**: Test carefully before editing

## Development Workflow

### Staging Environment Setup (December 2025)
**CRITICAL**: Development must occur in staging environment (`admin.soilsync.shop`) on `demo` branch, NOT directly in production.

#### Environment Configuration
- **Production**: `admin.middleworldfarms.org` (master branch) - LIVE SITE
- **Staging**: `admin.soilsync.shop` (demo branch) - DEVELOPMENT ENVIRONMENT

#### Development Process
1. **Work in Staging**:
   ```bash
   cd /opt/sites/admin.soilsync.shop
   git checkout demo
   # Make your changes
   ```

2. **Test in Staging**:
   ```bash
   php artisan test
   # Visit admin.soilsync.shop to verify functionality
   ```

3. **Deploy to Production**:
   ```bash
   # Option A: Automated deployment
   ./scripts/deployment/update-deploy.sh deploy production
   
   # Option B: GitHub Actions (automatic on PR merge)
   # Create PR: demo → master, merge triggers deployment
   ```

**Key Rules**:
- Never develop directly on `master` branch
- All changes in staging are safe and isolated
- Use UpdateTracking system for deployment logging
- Test thoroughly in staging before production deployment

## Critical Architecture Patterns

### Performance Requirements
**ALWAYS use local mirrored database tables for lookups**:
- ✅ Use `PlantVariety::where()` (local DB - fast ~50ms)
- ❌ NEVER use `$this->farmOSApi->getVariety()` for lookups (API - slow 2-30 seconds)

**FarmOS API is ONLY for**:
- Creating new records
- Updating existing records
- Initial sync/import operations
- NOT for read operations in user-facing features

**Local database tables that mirror FarmOS**:
- `plant_varieties` (PlantVariety model)
- `plant_assets` (PlantAsset model)
- `seeding_logs` (SeedingLog model)
- `harvests` (Harvest model)
- `field_beds` (FieldBed model)

**Response Time Targets**:
- Page loads: < 2 seconds
- API endpoints: < 200ms
- Database queries: < 50ms
- AI operations: < 3 seconds (acceptable for background operations)

### Query Best Practices
**❌ AVOID: Chained orWhere() for Different Fields**
```php
// BAD - Returns ANY matching record (first in database)
$variety = PlantVariety::where('farmos_id', $id)
    ->orWhere('farmos_tid', $id)
    ->orWhere('id', $id)
    ->first();
```

**✅ USE: Sequential Queries with Priority**
```php
// GOOD - Check each field separately with priority order
$variety = PlantVariety::where('farmos_id', $id)->first();

if (!$variety) {
    $variety = PlantVariety::where('farmos_tid', $id)->first();
}

if (!$variety) {
    $variety = PlantVariety::where('id', $id)->first();
}
```

### Git Workflow Best Practices
**⚠️ CRITICAL: Always Check Git History FIRST**

**BEFORE implementing ANY feature or fix:**

1. **Check if the code already exists**:
   ```bash
   git log --oneline --all --grep="<feature_name>" -10
   git show <commit_hash>:<file_path>
   ```

2. **Search for existing implementations**:
   ```bash
   grep -r "function <functionName>" .
   git log --all --oneline --follow <file_path>
   ```

3. **Check current git diff**:
   ```bash
   git diff HEAD <file_path>
   ```

4. **NEVER assume code is missing** - if a user reports something "stopped working", it likely means:
   - The code EXISTS but broke
   - Something changed that affected it
   - NOT that the code was never there

**When Things Break**:
1. **First response**: "Let me check the git history to see when this was working"
2. **Compare with working version**: `git diff <last_working_commit> HEAD -- <file>`
3. **Consider restoring**: `git checkout <commit> -- <file>` if breaking changes were made
4. **Then fix incrementally** rather than rewriting

### Multi-Database Configuration
The system integrates **three databases simultaneously**:
- **Laravel (`mysql`)**: Primary app database (deliveries, users, crop plans)
- **WordPress (`wordpress`)**: WooCommerce orders/subscriptions via direct connection
- **farmOS (`farmos`)**: Farm data access when available

**Models specify connections explicitly:**
```php
// WordPress models ALWAYS set this
protected $connection = 'wordpress';
protected $table = 'posts'; // WooCommerce uses custom post types
```

Key models: `WooCommerceOrder`, `WordPressUser`, `WooCommerceOrderMeta` use `wordpress` connection.

### Service Layer Architecture
Services in `app/Services/` handle external integrations:
- **`FarmOSApi`**: OAuth2 authentication via `FarmOSAuthService` singleton
- **`DirectDatabaseService`**: WordPress REST API wrapper (NOT direct DB queries)
- **`WeatherService`**: Met Office DataHub integration
- **`SymbiosisAIService`**: AI crop planning and chat (`app/Services/AI/`)

**Pattern**: Services encapsulate auth, caching, and error handling. Never bypass services for external APIs.

### Artisan Command Patterns
Custom commands in `app/Console/Commands/` use descriptive signatures:
```bash
php artisan farmos:sync-varieties --force --push-to-farmos
php artisan subscription:manage {email} --action=info
php artisan varieties:populate-harvest-windows --limit=50
```

**Convention**: Namespace commands by integration (`farmos:`, `subscription:`, `varieties:`).

## Running and Testing

### Running the Application
```bash
# Development (all services):
composer dev  # Runs server, queue, logs, vite concurrently

# Individual processes:
php artisan serve
php artisan queue:listen --tries=1
php artisan pail --timeout=0
npm run dev
```

### Testing Commands
```bash
composer test              # Runs config:clear + phpunit
php artisan test           # Direct PHPUnit execution
```

### Database Migrations
```bash
php artisan migrate        # Laravel tables only
# WordPress/farmOS databases are external - DO NOT migrate them
```

## farmOS Integration Specifics

### OAuth2 Authentication Flow
1. `FarmOSAuthService::getInstance()` maintains singleton
2. Token cached for 3600s, auto-refreshes
3. All API calls route through `FarmOSApi` methods

### Succession Planning Feature
Located in admin dashboard (`/admin/farmos/succession-planning`):
- Backward-planning from harvest windows
- Drag-drop timeline interface (Chart.js Gantt)
- Generates farmOS quick form URLs for seeding/transplanting/harvest logs
- AI calculates optimal planting dates via `HolisticAICropService`

**Data Flow**: User input → JS validation → AI processing → Laravel backend → farmOS quick forms

#### Recent Timeline Fixes (October 2025)
**Critical Bug Fixes:**
- **Missing `getBedOccupancy` Method**: Added to `FarmOSApi` service to fetch bed and planting data from farmOS API
- **Pagination Support**: Implemented full pagination for large bed datasets (174+ beds)
- **Block Organization**: Added intelligent block parsing from bed names (e.g., "3/1" → Block 3)
- **Bed Filtering**: Removed block header entries (named "Block X") to show only actual beds
- **Timeline Display**: Fixed filtering logic to show beds even when block organization is incomplete

**UI Improvements:**
- **Removed Duplicate Date Headers**: Eliminated duplicate month labels between main timeline and block headers
- **Hedgerow Representation**: Added visual hedgerow indicators between blocks to match real-world farm layout
- **Enhanced Block Headers**: Added hedgerow icons and improved spacing/layout

**API Endpoint**: `GET /admin/farmos/succession-planning/bed-occupancy`
- Returns: `{success: true, data: {beds: [...], plantings: [...]}}`
- Beds include: `id`, `name`, `block`, `status`, `land_type`
- Supports date range filtering for occupancy visualization

**Timeline Structure**:
- Beds grouped by blocks (Block 1, Block 3, etc.)
- Individual bed rows with drag-drop zones
- Real-time occupancy from farmOS land assets
- Succession indicators for planned harvest dates
- Visual hedgerow separators between blocks

#### Succession Planning Page Dependencies

**Main View File:**
- `resources/views/admin/farmos/succession-planning.blade.php` (6970 lines)
  - Extends `layouts.app`
  - Contains crop selection forms, harvest window inputs, bed dimensions
  - Timeline visualization with drag-and-drop functionality
  - AI chat interface for crop planning consultation
  - Export functionality (CSV download)
  - Extensive inline JavaScript for UI interactions and API calls

**JavaScript Dependencies:**
- External: SortableJS (CDN) for drag-and-drop functionality
- Local: `public/js/succession-planner.js` - SuccessionPlanner class managing UI state and event handlers

**Controller:**
- `app/Http/Controllers/Admin/SuccessionPlanningController.php` (2969 lines)
  - Methods: `index()`, `calculate()`, `generate()`, `createLogs()`, `chat()`, `getVariety()`, `getAIStatus()`, etc.
  - Integrates with `FarmOSApi`, `SymbiosisAIService`, `FarmOSQuickFormService`

**Routes (under `/admin/farmos/succession-planning/`):**
- `GET /` - Main interface (`index`)
- `POST /calculate` - Generate succession plan
- `POST /generate` - Create detailed plan
- `POST /create-logs` - Submit farmOS logs
- `POST /chat` - AI consultation
- `GET /varieties/{id}` - Variety details
- `GET /bed-occupancy` - **NEW**: Timeline bed occupancy data (returns beds + plantings)
- `GET /ai-status` - AI service status
- `POST /wake-ai` - Initialize AI service

**AI Integration:**
- Python service: `ai_service/app/main.py`
- Endpoint: `POST /api/v1/succession-planning/holistic`
- Provides sacred geometry, cosmic timing, biodynamic calendar alignment

**Services Used:**
- `FarmOSApi`: Crop/variety data, geometry assets
- `SymbiosisAIService`: AI chat and crop intelligence
- `FarmOSQuickFormService`: Generate quick form URLs for farmOS logging

**Models:**
- `PlantVariety`: Local variety database (synced from farmOS)
- Multi-connection access for farmOS data when needed

### AI Service Architecture
**Critical AI Processing Notes:**
- **CPU-ONLY SERVER**: All AI processing requires 60-90s timeouts minimum
- **No GPU access**: AI operations are CPU-bound and slow
- **Background processing**: AI features should be designed for async/background execution
- **Service location**: `ai_service/` directory contains Python AI service
- **Integration**: `SymbiosisAIService` handles AI chat and crop planning intelligence

**AI Endpoints:**
- Succession planning: `POST /api/v1/succession-planning/holistic`
- Chat interface: Integrated into succession planning UI
- Timeout handling: Always set 60-90s minimum for AI service calls

## WooCommerce Integration

### Order/Subscription Access
Access via `DirectDatabaseService` methods (REST API, not raw SQL):
```php
$service->searchUsers($query)      // Search customers
$service->generateUserSwitchUrl()  // Admin impersonation
```

**Critical**: WooCommerce stores orders as `post_type='shop_order'` in `posts` table. Use scopes:
```php
WooCommerceOrder::query() // Auto-scoped to shop_order/shop_subscription
```

### WooCommerce Subscription Replacement Strategy
**Goal**: Replace WooCommerce Subscriptions plugin with native Laravel subscription management while maintaining backward compatibility.

**Current Architecture** (Parallel Systems):
- **WooCommerce (Legacy)**: Still handles existing subscriptions via `shop_subscription` post type
- **Vegbox System (New)**: Native Laravel subscription management in `vegbox_subscriptions` table
- **Transition Period**: Both systems coexist - import from WooCommerce without modifying WC data

**Key Models**:
```php
// app/Models/VegboxSubscription.php - Native subscription management
protected $fillable = [
    'subscriber_id', 'subscriber_type', 'plan_id',
    'delivery_day', 'delivery_time', 'delivery_address_id',
    'next_billing_at', 'next_delivery_date',
    'woo_subscription_id',      // Link to WooCommerce ID
    'imported_from_woo',         // Flag for imported subs
    'skip_auto_renewal',         // Keep WC handling renewals
    'failed_payment_count', 'grace_period_ends_at', // Retry logic
];

// app/Models/VegboxPlan.php - Subscription plans (weekly/fortnightly boxes)
// app/Models/DeliverySchedule.php - Individual delivery dates/status
```

**Payment Integration**:
- **Service**: `VegboxPaymentService` (`app/Services/VegboxPaymentService.php`)
- **Flow**: Check balance → Charge via MWF API → Update subscription → Send notifications
- **Retry Logic**: Grace period tracking with configurable retry attempts
- **Stripe Integration**: Webhook handling for payout matching (`STRIPE_WEBHOOK_SECRET`)

**Artisan Commands**:
```bash
# Safe import from WooCommerce (READ-ONLY, no WC modifications)
php artisan vegbox:import-woo-subscriptions --dry-run
php artisan vegbox:import-woo-subscriptions --skip-renewals  # Import but keep WC renewals

# Generate delivery schedules from active subscriptions
php artisan vegbox:generate-delivery-schedules

# Manual renewal processing
php artisan vegbox:process-renewals --dry-run
```

**Routes** (`/admin/vegbox-subscriptions/`):
- `GET /` - Dashboard with active/cancelled stats
- `GET /upcoming-renewals` - Subscriptions renewing within N days
- `GET /failed-payments` - Recent payment failures requiring attention
- `POST /{id}/manual-renewal` - Trigger manual renewal attempt
- `POST /{id}/cancel` - Cancel subscription
- `POST /{id}/reactivate` - Reactivate cancelled subscription

**Controller**: `VegboxSubscriptionController` (`app/Http/Controllers/Admin/VegboxSubscriptionController.php`)
- Dashboard views, renewal management, payment retry interface
- Integrates with `VegboxPaymentService` for all payment operations

**Critical Conventions**:
- **NEVER modify WooCommerce data during import** - read-only operations only
- **Use `skip_auto_renewal` flag** to let WC handle renewals during transition
- **Always check `woo_subscription_id`** to identify imported subscriptions
- **Grace period handling**: `SUBSCRIPTION_GRACE_PERIOD_DAYS=7` (configurable)
- **Retry delays**: `SUBSCRIPTION_RETRY_DELAYS="2,4,6"` (days between attempts)

### Customer Portal Integration
**Current State**: Customers access subscriptions via **WooCommerce My Account** (`https://middleworldfarms.org/my-account/`), not a native Laravel portal.

**Admin-to-Customer Workflow**:
- **User Switching**: Admin can impersonate customers via delivery schedule interface
- **Service**: `WordPressUserService` (`app/Services/WordPressUserService.php`)
- **Routes**: `/admin/users/switch/{userId}` - generates WooCommerce switch URL
- **Flow**: Admin clicks "Switch" → Opens WooCommerce My Account as that customer → Admin can view/manage on customer's behalf

**Delivery Schedule Connection to WooCommerce**:
- **Current**: Delivery schedules pull from WooCommerce Subscriptions API (READ-ONLY)
- **Service**: `WpApiService::getDeliveryScheduleData()` fetches active subscriptions
- **Display**: `/admin/deliveries` shows deliveries/collections with WC subscription data
- **Week Assignment**: Admin can update fortnightly week type (A/B) via meta updates
- **Real-time**: Delivery schedule reflects current WooCommerce subscription status

**Customer-Facing Features** (via WooCommerce):
- View subscription status and next billing date
- Update delivery address and preferences
- Pause/skip deliveries (WooCommerce functionality)
- View order history and download invoices
- Manage payment methods (Stripe integration)

**Future Migration Path** (when Vegbox system takes over):
- Phase 1: Keep WooCommerce My Account for customers, admin uses Vegbox backend
- Phase 2: Build native customer portal in Laravel for subscription management
- Phase 3: Migrate customers to native portal with seamless WooCommerce data import

## Configuration Conventions

### Environment Variables
- `FARMOS_*`: farmOS OAuth2 credentials
- `WP_DB_*`: WordPress database connection (separate from main DB)
- `FARMOS_DB_*`: farmOS database (optional direct access)

### Route Structure
- `/admin/*`: Protected admin routes (`admin.auth` middleware)
- `/api/conversations/*`: Secured API endpoints (same auth)
- Prefixes: `admin.` for named routes

## Key Files Reference

### Core Services
- `app/Services/FarmOSApi.php`: farmOS API client
  - **NEW**: `getBedOccupancy($startDate, $endDate)` - Fetches beds and plantings with pagination
  - OAuth2 authentication via `FarmOSAuthService` singleton
  - Methods: `getAvailableCropTypes()`, `getGeometryAssets()`, `getHarvestLogs()`
- `app/Services/DirectDatabaseService.php`: WordPress integration
- `app/Services/AI/SymbiosisAIService.php`: AI crop intelligence

### Models
- Multi-connection: `app/Models/WooCommerceOrder.php` (example)
- Laravel-only: `app/Models/PlantVariety.php`, `app/Models/CropPlan.php`
- **Vegbox subscriptions**: `app/Models/VegboxSubscription.php`, `app/Models/VegboxPlan.php`, `app/Models/DeliverySchedule.php`

### Services
- **Payment processing**: `app/Services/VegboxPaymentService.php` (subscription renewals, Stripe integration)
- **Delivery management**: `app/Services/DeliveryScheduleService.php` (schedule generation from subscriptions)

### Configuration
- `config/database.php`: Three-database setup (lines 75-120)
- `config/services.php`: WooCommerce, Stripe, and payment API credentials
- `.env`: Environment variables (274 lines with extensive service integrations)
  - `SUBSCRIPTION_GRACE_PERIOD_DAYS=7` - Payment retry grace period
  - `SUBSCRIPTION_MAX_RETRY_ATTEMPTS=3` - Maximum renewal retry attempts
  - `SUBSCRIPTION_RETRY_DELAYS="2,4,6"` - Days between retry attempts
  - `STRIPE_WEBHOOK_SECRET` - Webhook verification for payout matching

## Common Pitfalls

1. **Never query WordPress/farmOS DBs directly** - use services
2. **Check connection property** when creating new models
3. **Import without modification** - WooCommerce import commands are READ-ONLY
4. **CPU-only AI processing** - set timeouts to 60-90 seconds minimum for AI service calls
5. **Production environment** - all changes go live immediately, no dev server needed
6. **OAuth tokens expire** - always use `FarmOSAuthService` singleton
7. **Queue workers required** - delivery automation depends on `queue:listen`
8. **Artisan namespaces** - follow existing patterns (`farmos:`, `subscription:`, `vegbox:`)
9. **JavaScript function duplication** - Remove duplicate function definitions (e.g., `submitAllQuickForms` was defined twice)
10. **FarmOS bed data** - Beds are land assets, not activities; use proper pagination for large datasets
11. **Subscription transition period** - Both WooCommerce and Vegbox systems run in parallel; use `skip_auto_renewal` flag appropriately
12. **Git history first** - Always check git history before implementing features; code likely exists but may be broken
13. **Staging development** - Never develop directly on master branch; use demo branch in staging environment

## Migration Path: WooCommerce → Vegbox Subscriptions

**Current State** (December 2025):
- WooCommerce Subscriptions plugin still active and handling renewals
- Native Vegbox subscription system implemented and ready for production
- Import command safely copies data without modifying WooCommerce

**Recommended Transition Steps**:
1. **Phase 1 (Current)**: Import existing subscriptions with `--skip-renewals` flag to keep WC handling renewals
2. **Phase 2**: New subscriptions created directly in Vegbox system (bypass WC entirely)
3. **Phase 3**: Gradually migrate existing subscriptions to Vegbox renewals (remove `skip_auto_renewal` flag)
4. **Phase 4**: Disable WooCommerce Subscriptions plugin once all subscriptions migrated
5. **Phase 5**: Archive WooCommerce subscription data, fully native operation

**Safety Mechanisms**:
- `--dry-run` flag for testing import without database changes
- `skip_auto_renewal` prevents duplicate billing during transition
- `woo_subscription_id` maintains backward traceability
- Grace period and retry logic prevent missed payments

## Testing Practices

- Feature tests in `tests/Feature/`
- Current: `SuccessionPlanningTest` (basic structure)
- Use RefreshDatabase trait for Laravel DB, mock external services

## Documentation Links

- Main README: Project setup and features
- SUCCESSION_PLANNER_README.md: Complete workflow for succession planning
- CONTRIBUTING.md: Development setup (Docker/traditional)
