# Multi-Farm Database Portability

## Overview
This system is designed to work across different farms with **zero hardcoded database names or table prefixes**. All database connections are configured via environment variables.

## Database Configuration Per Farm

Each farm sets its own database configuration in `.env`:

```env
# Main Laravel Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_demo        # Farm-specific database name
DB_USERNAME=admin_demo        # Farm-specific username
DB_PASSWORD=SecurePassword    # Farm-specific password

# WordPress Database Connection
WORDPRESS_DB_HOST=127.0.0.1
WORDPRESS_DB_PORT=3306
WORDPRESS_DB_DATABASE=wp_demo         # Farm-specific WordPress database
WORDPRESS_DB_USERNAME=wp_demo         # Farm-specific WordPress username
WORDPRESS_DB_PASSWORD=SecurePassword  # Farm-specific WordPress password
WP_DB_PREFIX=demo_wp_                # Farm-specific table prefix (e.g., 'wp_', 'D6sPMX_', 'demo_wp_')

# farmOS Database Connection (optional)
FARMOS_DB_HOST=127.0.0.1
FARMOS_DB_PORT=3306
FARMOS_DB_DATABASE=farmos_db          # Farm-specific farmOS database
FARMOS_DB_USERNAME=farmos_user        # Farm-specific farmOS username
FARMOS_DB_PASSWORD=SecurePassword     # Farm-specific farmOS password
```

## How Portability is Achieved

### 1. Laravel Models Use Named Connections
All models that access WordPress data explicitly declare their connection:

```php
// app/Models/WordPressUser.php
protected $connection = 'wordpress';  // Uses config/database.php 'wordpress' connection
```

This connection reads from the environment variables above, so no database names are hardcoded.

### 2. Dynamic Table Prefixes
WordPress installations can use custom table prefixes (default `wp_`, but often randomized for security like `D6sPMX_`).

**❌ Wrong (Hardcoded):**
```php
DB::connection('wordpress')->table('usermeta')->insert([
    'meta_key' => 'wp_capabilities',  // Breaks if prefix isn't 'wp_'
]);
```

**✅ Correct (Dynamic):**
```php
$prefix = config('database.connections.wordpress.prefix');  // Gets from WP_DB_PREFIX env var
DB::connection('wordpress')->table('usermeta')->insert([
    'meta_key' => $prefix . 'capabilities',  // Works with any prefix
]);
```

### 3. Controllers Use Model Queries
All controllers use Eloquent models which automatically use the configured connection:

```php
// app/Http/Controllers/Admin/CustomerManagementController.php
$query = WordPressUser::query();  // Uses 'wordpress' connection from model
```

No need to specify `DB::connection('wordpress')` because the model handles it.

### 4. Services Abstract External APIs
External services (farmOS, weather APIs) use service classes with configurable endpoints:

```php
// config/services.php
'farmos' => [
    'url' => env('FARMOS_URL'),  // Each farm has own farmOS URL
    'client_id' => env('FARMOS_CLIENT_ID'),
    'client_secret' => env('FARMOS_CLIENT_SECRET'),
],
```

## Verification Test

To verify your installation is portable, check these files have **NO** hardcoded database names:

```bash
# Search for potential hardcoded database references
grep -r "demo_wp_" app/  # Should find NONE
grep -r "admin_demo" app/  # Should find NONE
grep -r "wp_demo" app/  # Should find NONE

# All database references should use config() or env()
grep -r "config('database.connections" app/  # Should find dynamic prefix usage
```

## Common Mistakes to Avoid

### ❌ Don't Do This:
```php
// Hardcoded database name
DB::connection('mysql')->select('SELECT * FROM admin_demo.users');

// Hardcoded table prefix
$meta = DB::connection('wordpress')->table('usermeta')
    ->where('meta_key', 'wp_capabilities');  // Breaks with custom prefixes

// Direct database in query
$users = DB::select('SELECT * FROM wp_demo.wp_users');
```

### ✅ Do This Instead:
```php
// Use Eloquent models with configured connections
$users = User::all();  // Uses default 'mysql' connection

// Use dynamic prefix
$prefix = config('database.connections.wordpress.prefix');
$meta = DB::connection('wordpress')->table('usermeta')
    ->where('meta_key', $prefix . 'capabilities');

// Use named connections
$users = DB::connection('wordpress')->table('users')->get();
```

## Setup for New Farms

1. **Copy `.env.example` to `.env`**
2. **Set farm-specific values:**
   - All `DB_*` variables for main Laravel database
   - All `WORDPRESS_DB_*` variables for WordPress connection
   - `WP_DB_PREFIX` to match WordPress installation (check `wp-config.php`)
3. **Run setup commands:**
   ```bash
   php artisan migrate  # Create Laravel tables
   php artisan subscriptions:sync-wp-users  # Create WordPress users for imported subs
   ```

4. **WordPress Plugin Setup:**
   - Install `mwf-integration` plugin (user switching, API endpoints)
   - Install `mwf-custom-subscriptions` plugin (My Account interface)
   - Configure `wp-config.php`:
     ```php
     define('MWF_API_URL', 'https://admin.yourfarm.com');
     define('MWF_API_KEY', 'your-api-key-from-laravel-env');
     ```

## Why This Matters

**Problem:** If database names/prefixes are hardcoded, every farm would need to:
- Use identical database names (security risk)
- Manually find/replace all hardcoded values (error-prone)
- Fork the codebase for their specific configuration (maintenance nightmare)

**Solution:** Environment-based configuration means:
- ✅ Each farm has unique database credentials
- ✅ Same codebase works for all farms
- ✅ Easy to test locally with different database names
- ✅ Git repository contains no sensitive credentials
- ✅ Open source ready - no farm-specific data in code

## Current Status

**✅ Fully Portable Components:**
- All Eloquent models (`WordPressUser`, `WooCommerceOrder`, etc.)
- All controllers (`CustomerManagementController`, `UserSwitchingController`, etc.)
- All services (`WpApiService`, `FarmOSApi`, etc.)
- All commands (`SyncWordPressUsers`, etc.)
- Database configuration (`config/database.php`)

**✅ Verified Working:**
- Customers page (`/admin/customers`) - 19 customers displayed
- User switching functionality - generates WordPress auto-login URLs
- Subscription management - reads WooCommerce subscriptions
- Multi-database queries - Laravel + WordPress + farmOS

## Related Documentation

- `docs/MWF-INTEGRATION-README.md` - WordPress integration details
- `.github/copilot-instructions.md` - Architecture overview (multi-database section)
- `config/database.php` - Database connection configuration
