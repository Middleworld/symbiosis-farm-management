# farmOS OAuth2 Setup Guide for Laravel Admin Integration

## 🚀 Quick Setup (5 Minutes)

Complete checklist for setting up OAuth2 between farmOS and Laravel admin application.

### Prerequisites
- farmOS 3.x installed and running
- Laravel admin application installed
- Shell access to both servers
- Admin access to farmOS UI

---

## Step 1: Generate RSA Key Pair (CRITICAL!)

**⚠️ MOST COMMON FAILURE POINT** - Missing or unreadable keys cause "server_error" 500 responses.

```bash
# Navigate to farmOS root directory
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com

# Create keys directory
mkdir -p keys

# Generate 2048-bit RSA private key
openssl genrsa -out keys/private.key 2048

# Extract public key from private key
openssl rsa -in keys/private.key -pubout -out keys/public.key

# Set proper permissions
chmod 640 keys/private.key  # Owner read/write, group read only
chmod 644 keys/public.key   # Everyone can read public key

# Set ownership to web server user (adjust for your system)
# For Debian/Ubuntu: www-data
# For CentOS/RHEL: apache
# For Plesk: your site user (e.g., demo_shop_user)
chown -R www-data:www-data keys/

# Verify files created successfully
ls -la keys/
```

**Expected output:**
```
drwxr-xr-x 2 www-data www-data 4096 Dec 29 19:08 .
-rw-r----- 1 www-data www-data 1704 Dec 29 19:08 private.key
-rw-r--r-- 1 www-data www-data  451 Dec 29 19:08 public.key
```

### Configure simple_oauth Module

```bash
# Point simple_oauth to the key files (path relative to farmOS root)
./vendor/bin/drush config:set simple_oauth.settings public_key ../keys/public.key -y
./vendor/bin/drush config:set simple_oauth.settings private_key ../keys/private.key -y

# Rebuild cache to apply configuration
./vendor/bin/drush cache:rebuild
```

### Troubleshooting Key Issues

| Error Message | Cause | Solution |
|--------------|-------|----------|
| `Failed to open stream: No such file or directory` | Keys not generated | Run openssl commands above |
| `Failed to open stream: Permission denied` | Wrong file ownership | Run `chown` command with correct user |
| `The authorization server encountered an unexpected condition` | Keys not configured in Drupal | Run `drush config:set` commands |
| Key files exist but still getting errors | Cache not cleared | Run `drush cache:rebuild` |

---

## Step 2: Create OAuth Consumer

### Method A: Via UI (Recommended)

1. **Navigate to Consumer Management:**
   ```
   https://farmos.yoursite.com/admin/config/services/consumer/add
   ```
   Or: **Configuration → Web Services → Consumers → Add Consumer**

2. **Fill in Consumer Form:**

   | Field | Value | Notes |
   |-------|-------|-------|
   | **Label** | `Laravel Admin` | Friendly name for this client |
   | **Description** | `Laravel admin integration` | Optional description |
   | **User** | Select admin user | User this client acts as (needs `farm_manager` role) |
   | **New Secret** | Strong password | **Save this!** Cannot retrieve later |
   | **Is this consumer confidential?** | ✅ **Yes** | Required for server-to-server auth |

3. **Grant Types (Enable ALL):**
   - ✅ **Client Credentials** - Server-to-server authentication
   - ✅ **Password** - Username/password flow (optional)
   - ✅ **Refresh Token** - Token renewal without re-authentication

4. **Scopes:**
   - Enter: `farm_manager`
   - Or leave empty for full access
   - Multiple scopes: comma-separated

5. **Redirect URI:**
   ```
   https://admin.yoursite.com/oauth/callback
   ```
   **Must match exactly** what Laravel expects!

6. **Token Settings:**
   - **Access token expiration**: `3600` (1 hour)
   - **Refresh token expiration**: Leave default or set to `604800` (1 week)

7. **Click "Save"**

8. **Copy Credentials Immediately:**
   - **Client ID**: Long alphanumeric string (e.g., `OoX1zV1S9PLEsIzwBldh4LoxGKRVoWuVPEyauf04KLo`)
   - **Client Secret**: The password you just entered
   - **⚠️ Store securely** - Secret cannot be retrieved after leaving this page!

### Method B: Via Drush (Advanced)

```bash
# Navigate to farmOS directory
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com

# Create consumer (note: this method requires simple_oauth_extras module)
./vendor/bin/drush scr scripts/create_oauth_consumer.php

# Alternatively, insert directly into database (not recommended)
# See farmOS simple_oauth documentation for SQL approach
```

---

## Step 3: Configure Laravel Admin Application

### Update `.env` File

```bash
# Navigate to Laravel admin directory
cd /var/www/vhosts/yoursite.com/admin.yoursite.com

# Edit .env file
nano .env
```

**Add/Update farmOS OAuth Configuration:**

```ini
# farmOS Integration
FARMOS_URL=https://farmos.yoursite.com

# OAuth2 Credentials
FARMOS_OAUTH_CLIENT_ID=OoX1zV1S9PLEsIzwBldh4LoxGKRVoWuVPEyauf04KLo
FARMOS_OAUTH_CLIENT_SECRET=Demo2025!FarmOS#Laravel$Sync%Admin^Stage&Test
FARMOS_OAUTH_SCOPE=farm_manager

# farmOS Database (for direct queries - optional but faster)
FARMOS_DB_HOST=127.0.0.1
FARMOS_DB_PORT=3306
FARMOS_DB_DATABASE=farmos_database_name
FARMOS_DB_USERNAME=farmos_db_user
FARMOS_DB_PASSWORD=farmos_db_password
```

**⚠️ Important Variable Names:**
- Use `FARMOS_OAUTH_CLIENT_ID` (with `OAUTH_` prefix)
- Use `FARMOS_OAUTH_CLIENT_SECRET` (with `OAUTH_` prefix)
- **NOT** `FARMOS_CLIENT_ID` - wrong variable name will cause authentication failures

### Verify config/farmos.php Uses Correct Variables

```php
// config/farmos.php should have:
'client_id' => env('FARMOS_OAUTH_CLIENT_ID'),
'client_secret' => env('FARMOS_OAUTH_CLIENT_SECRET'),
'oauth_scope' => env('FARMOS_OAUTH_SCOPE', 'farm_manager'),
```

### Clear Configuration Cache

```bash
# Clear Laravel config cache
php artisan config:clear

# Restart PHP-FPM (if using PHP-FPM)
pkill -f "pool admin.yoursite.com"
# Or: systemctl reload php8.3-fpm
```

---

## Step 4: Configure farmOS Database Connection (Optional but Recommended)

**Why?** Direct database queries are 10-100x faster than API calls for reading data.

### Find farmOS Database Credentials

```bash
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com

# Check Drupal settings.php for database config
grep -A 10 "^\$databases\[" web/sites/default/settings.php
```

**Example output:**
```php
$databases['default']['default'] = [
  'database' => 'farmos_production',
  'username' => 'farmos_user',
  'password' => 'SecurePassword123',
  'host' => 'localhost',
  'port' => '3306',
  'driver' => 'mysql',
];
```

### Update Laravel .env with Database Credentials

```ini
# farmOS Database Connection
FARMOS_DB_HOST=127.0.0.1
FARMOS_DB_PORT=3306
FARMOS_DB_DATABASE=farmos_production
FARMOS_DB_USERNAME=farmos_user
FARMOS_DB_PASSWORD=SecurePassword123
```

### Verify config/database.php Has farmOS Connection

```php
// config/database.php
'farmos' => [
    'driver' => 'mysql',  // farmOS typically uses MySQL
    'host' => env('FARMOS_DB_HOST', '127.0.0.1'),
    'port' => env('FARMOS_DB_PORT', '3306'),
    'database' => env('FARMOS_DB_DATABASE', 'farmos'),
    'username' => env('FARMOS_DB_USERNAME', 'root'),
    'password' => env('FARMOS_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => false,
],
```

**Note:** farmOS 3.x uses **MySQL** (not PostgreSQL). Earlier versions used PostgreSQL.

---

## Step 5: Test OAuth Connection

### Via Laravel Admin UI

1. **Navigate to Settings:**
   ```
   https://admin.yoursite.com/admin/settings
   ```

2. **Click on "farmOS Integration" section**

3. **Verify connection status shows:**
   - ✅ **OAuth configured** (green badge)
   - farmOS URL displayed correctly

4. **Click "Test Connection" button**

5. **Verify both connections succeed:**
   - ✅ **API Connection Success** - Shows number of plant types found
   - ✅ **Database Connection Success** - Shows number of varieties and beds

### Via Command Line (cURL)

```bash
# Test token request directly
curl -X POST 'https://farmos.yoursite.com/oauth/token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'grant_type=client_credentials' \
  --data-urlencode 'client_id=YOUR_CLIENT_ID' \
  --data-urlencode 'client_secret=YOUR_CLIENT_SECRET'
```

**Expected successful response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Test API call with token:**
```bash
# Use token from above
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

curl -H "Authorization: Bearer $TOKEN" \
  https://farmos.yoursite.com/api
```

---

## Common Issues & Solutions

### Issue: "400 Bad Request - invalid_request"

**Causes:**
- Missing or incorrect scope parameter
- Malformed request body
- URL encoding issues with special characters in secret

**Solutions:**
```bash
# Use --data-urlencode for proper encoding
curl --data-urlencode "client_secret=Your!Complex#Password"

# Remove scope parameter if not needed
# Client credentials grant may not require scope

# Test without scope first:
curl -X POST 'https://farmos.yoursite.com/oauth/token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'grant_type=client_credentials' \
  --data-urlencode 'client_id=YOUR_CLIENT_ID' \
  --data-urlencode 'client_secret=YOUR_CLIENT_SECRET'
```

### Issue: "500 Internal Server Error - server_error"

**Cause:** RSA keys missing, wrong permissions, or not configured

**Check farmOS logs:**
```bash
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com
./vendor/bin/drush watchdog:show --type=php --count=10
```

**Look for:**
- `Failed to open stream: No such file or directory` → Generate keys (Step 1)
- `Permission denied` → Fix ownership: `chown -R www-data:www-data keys/`
- No key-related errors → Keys not configured in Drupal settings

**Solution:**
```bash
# Regenerate and configure keys
mkdir -p keys
openssl genrsa -out keys/private.key 2048
openssl rsa -in keys/private.key -pubout -out keys/public.key
chown -R www-data:www-data keys/
chmod 640 keys/private.key
chmod 644 keys/public.key

./vendor/bin/drush config:set simple_oauth.settings public_key ../keys/public.key -y
./vendor/bin/drush config:set simple_oauth.settings private_key ../keys/private.key -y
./vendor/bin/drush cache:rebuild
```

### Issue: "FarmOS authentication failed"

**Causes:**
- Wrong variable names in Laravel .env
- Client ID or secret incorrect
- OAuth consumer not saved properly in farmOS

**Solutions:**

1. **Verify .env variable names:**
   ```ini
   # CORRECT:
   FARMOS_OAUTH_CLIENT_ID=...
   FARMOS_OAUTH_CLIENT_SECRET=...
   
   # WRONG:
   FARMOS_CLIENT_ID=...  # Missing OAUTH_ prefix
   ```

2. **Clear Laravel cache:**
   ```bash
   php artisan config:clear
   pkill -f "pool admin.yoursite.com"
   ```

3. **Verify consumer in farmOS database:**
   ```bash
   cd /var/www/vhosts/yoursite.com/farmos.yoursite.com
   ./vendor/bin/drush sql:query \
     "SELECT client_id, label FROM consumer_field_data WHERE client_id = 'YOUR_CLIENT_ID';"
   ```

### Issue: "Database connection failed - Table doesn't exist"

**Cause:** Wrong database name or using PostgreSQL config for MySQL database

**Solutions:**

1. **Verify farmOS database type:**
   ```bash
   cd /var/www/vhosts/yoursite.com/farmos.yoursite.com
   grep "'driver'" web/sites/default/settings.php
   ```

2. **Update Laravel config/database.php:**
   - If farmOS uses `mysql` → Set `'driver' => 'mysql'`
   - If farmOS uses `pgsql` → Set `'driver' => 'pgsql'`

3. **Check database name:**
   ```bash
   # In farmOS directory
   ./vendor/bin/drush status | grep "Database"
   ```

4. **Update .env with correct database name:**
   ```ini
   FARMOS_DB_DATABASE=actual_database_name
   ```

### Issue: "Password authentication failed for user"

**Cause:** Incorrect database username or password in Laravel .env

**Solution:**
```bash
# Get correct credentials from farmOS settings.php
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com
grep -A 5 "^\$databases\[" web/sites/default/settings.php | grep -E "username|password"

# Update Laravel .env with correct values
```

---

## Production Checklist

Before deploying to production:

### Security
- [ ] RSA private key has `640` permissions (not world-readable)
- [ ] Keys directory owned by web server user only
- [ ] Client secret is strong (16+ characters, mixed case, special chars)
- [ ] OAuth consumer set to "Confidential"
- [ ] farmOS database user has read-only permissions (if using direct DB access)

### Configuration
- [ ] farmOS URL uses HTTPS (not HTTP)
- [ ] Redirect URI matches exactly (trailing slash matters!)
- [ ] Token expiration appropriate for your use case (3600s = 1 hour recommended)
- [ ] Laravel .env uses `FARMOS_OAUTH_*` variable names (not `FARMOS_CLIENT_*`)
- [ ] config/farmos.php reads from correct .env variables

### Testing
- [ ] OAuth token request succeeds (cURL test)
- [ ] Laravel admin can authenticate via API
- [ ] Direct database queries work (if configured)
- [ ] Both connections show green in Laravel settings page
- [ ] Test data sync (varieties, beds, harvest logs)

### Monitoring
- [ ] Set up farmOS watchdog monitoring for OAuth errors
- [ ] Laravel logs configured to capture authentication failures
- [ ] Token refresh works automatically when tokens expire
- [ ] Database connection pooling configured if using direct DB access

---

## Summary

**What we configured:**

1. **farmOS OAuth Server:**
   - RSA key pair for JWT signing
   - OAuth consumer with client credentials grant
   - Token expiration and scopes

2. **Laravel Admin Client:**
   - OAuth credentials in .env
   - Database connection for fast reads
   - Connection testing interface

3. **Integration Architecture:**
   - **API (OAuth)**: For writes - creating logs, updating records
   - **Direct DB**: For reads - querying varieties, beds, harvests (100x faster)

**Performance Pattern:**
- ✅ **Read from database** - Fast local queries
- ✅ **Write via API** - Validates business logic, triggers hooks
- ❌ **Don't read via API** - Slow, pagination overhead

**Next Steps:**
- Test succession planner functionality
- Verify harvest log sync
- Set up automated variety sync cron job

---

## Reference Commands

### Quick Diagnostics

```bash
# Check farmOS OAuth logs
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com
./vendor/bin/drush watchdog:show --type=php --count=5

# Test OAuth token manually
curl -X POST 'https://farmos.yoursite.com/oauth/token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'grant_type=client_credentials' \
  --data-urlencode 'client_id=YOUR_CLIENT_ID' \
  --data-urlencode 'client_secret=YOUR_CLIENT_SECRET'

# Test API endpoint with token
curl -H "Authorization: Bearer TOKEN" \
  https://farmos.yoursite.com/api/taxonomy_term/plant_type

# Clear Laravel config cache
cd /var/www/vhosts/yoursite.com/admin.yoursite.com
php artisan config:clear
pkill -f "pool admin.yoursite.com"

# Clear farmOS cache
cd /var/www/vhosts/yoursite.com/farmos.yoursite.com
./vendor/bin/drush cache:rebuild
```

### Key File Locations

| Component | Path | Purpose |
|-----------|------|---------|
| OAuth Keys | `farmos.yoursite.com/keys/` | RSA key pair for JWT signing |
| farmOS Config | `web/sites/default/settings.php` | Database credentials |
| Laravel Config | `admin.yoursite.com/.env` | OAuth and DB credentials |
| farmOS Service | `app/Services/FarmOSApi.php` | API client |
| Auth Service | `app/Services/FarmOSAuthService.php` | Token management |
| Settings Controller | `app/Http/Controllers/Admin/SettingsController.php` | Connection testing |

---

## Support

For issues not covered in this guide:

1. **Check farmOS logs:** `drush watchdog:show --type=php`
2. **Check Laravel logs:** `tail -f storage/logs/laravel.log`
3. **Review farmOS OAuth documentation:** https://farmos.org/development/api/authentication/
4. **Test OAuth flow manually** with cURL to isolate issues

**Common Documentation:**
- [farmOS API Docs](https://farmos.org/development/api/)
- [simple_oauth Module](https://www.drupal.org/project/simple_oauth)
- [Laravel OAuth Clients](https://laravel.com/docs/http-client)
