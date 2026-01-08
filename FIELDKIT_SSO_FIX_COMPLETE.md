# FieldKit SSO Fix - January 7, 2026

## CRITICAL FIX - Laravel Passport Client Secret

**Problem**: Laravel Passport requires client secrets to be **bcrypt hashed**, but after git reset they were plain text hex strings.

**Solution**:
```bash
# 1. Hash the secret
php -r "echo password_hash('DemoLaravelSync2025', PASSWORD_BCRYPT);"
# Output: $2y$10$YHt7unouEvjUiYNwH43PAe8/CO8//HIKWJhmDjqbQA6w/JyxbdvQ.

# 2. Update Laravel database (staging: admin_demo, production: check .env)
mysql -e "UPDATE admin_demo.oauth_clients SET secret = '\$2y\$10\$YHt7unouEvjUiYNwH43PAe8/CO8//HIKWJhmDjqbQA6w/JyxbdvQ.' WHERE id = '019b79d1-29cd-7078-8662-d4413a597a1f'"
```

**To Apply to Production**:
1. SSH to production server
2. Check database name: `grep DB_DATABASE /opt/sites/admin.middleworldfarms.org/.env`
3. Run same UPDATE query with production database name
4. Test "Log in with Generic" button on farmOS

---

## Problem
After domain change from `feildkit.soilsync.shop` to `fieldkit.soilsync.shop`, SSO stopped working:
- FieldKit couldn't access farmOS OAuth tokens
- Simple OAuth consumer management UI was inaccessible in staging
- CORS and session issues preventing token exchange

## Root Cause
1. **Missing Permission**: `farm_manager` role didn't have "administer simple_oauth entities" permission in staging
2. **Missing OAuth Consumers**: Working OAuth consumers from backup weren't recreated in staging
3. **CORS Configuration**: Session cookies and CORS headers not properly configured

## Solution

### 1. Grant Simple OAuth Admin Permission
```bash
cd /var/www/vhosts/soilsync.shop/farmos.soilsync.shop
./vendor/bin/drush role:perm:add farm_manager "administer simple_oauth entities"
./vendor/bin/drush cr
```

### 2. Created OAuth Consumers via Drupal API
```bash
./drush-safe php:eval "
\$storage = \Drupal::entityTypeManager()->getStorage('consumer');

// OpenID consumer for SSO
\$openid = \$storage->create([
  'client_id' => '019b79d1-29cd-7078-8662-d4413a597a1f',
  'label' => 'Laravel Admin OpenID',
  'secret' => 'DemoLaravelSync2025',
  'confidential' => TRUE,
  'third_party' => FALSE,
  'grant_types' => ['authorization_code', 'refresh_token'],
]);
\$openid->save();

// API consumer
\$api = \$storage->create([
  'client_id' => 'OoX1zV1S9PLEsIzwBldh4LoxGKRVoWuVPEyauf04KLo',
  'label' => 'Laravel Admin API',
  'secret' => 'DemoLaravelSync2025',
  'confidential' => TRUE,
  'third_party' => FALSE,
  'grant_types' => ['client_credentials', 'password'],
]);
\$api->save();
"
```

### 3. Restored Working OAuth Credentials in Laravel .env
```env
FARMOS_OAUTH_CLIENT_ID=OoX1zV1S9PLEsIzwBldh4LoxGKRVoWuVPEyauf04KLo
FARMOS_OAUTH_CLIENT_SECRET=DemoLaravelSync2025
FARMOS_OAUTH_SCOPE=farm_manager

FARMOS_OPENID_CLIENT_ID=019b79d1-29cd-7078-8662-d4413a597a1f
FARMOS_OPENID_CLIENT_SECRET=DemoLaravelSync2025
```

### 4. Previous CORS/Session Fixes (Already Applied)
- Added CSRF exception for `/sso/farmos-tokens` in `VerifyCsrfToken.php`
- Created `SsoCorsMiddleware` for proper CORS headers
- Fixed session regeneration order in `SsoController::authenticate()`
- Set `SESSION_SAME_SITE=none` and `SESSION_SECURE_COOKIE=true`

## Verification
1. Access Simple OAuth settings: `https://farmos.soilsync.shop/admin/config/services/consumer`
2. Open FieldKit: `https://fieldkit.soilsync.shop`
3. Should auto-login via SSO if logged into admin.soilsync.shop

## Key Lesson
**CRITICAL**: Direct database updates to OAuth consumers don't work. Must use Drupal entity API via `drush php:eval` to properly create consumers with all required relationships and hooks.

## Backup Used
Restored credentials from: `/var/www/vhosts/soilsync.shop/backups/SSO_ALL_WORKING_20260106_1817.tar.gz`

Working commit: `00f4b8c4` - "Fix SSO for all 4 sites: WordPress, farmOS, FieldKit, Admin"
