# farmOS OAuth Setup - Quick Reference Card

**⏱️ Setup Time: 5 minutes**

## 🔑 Generate Keys (Step 1 - Most Important!)

```bash
cd /path/to/farmos
mkdir -p keys
openssl genrsa -out keys/private.key 2048
openssl rsa -in keys/private.key -pubout -out keys/public.key
chmod 640 keys/private.key
chmod 644 keys/public.key
chown -R www-data:www-data keys/  # Or your web user

./vendor/bin/drush config:set simple_oauth.settings public_key ../keys/public.key -y
./vendor/bin/drush config:set simple_oauth.settings private_key ../keys/private.key -y
./vendor/bin/drush cache:rebuild
```

## 🔐 Create OAuth Consumer (Step 2)

**Visit:** `https://farmos.yoursite.com/admin/config/services/consumer/add`

| Setting | Value |
|---------|-------|
| Label | `Laravel Admin` |
| User | Admin with `farm_manager` role |
| Secret | Strong password (save it!) |
| Confidential | ✅ Yes |
| Grant Types | ✅ Client Credentials<br>✅ Password<br>✅ Refresh Token |
| Redirect URI | `https://admin.yoursite.com/oauth/callback` |
| Scopes | `farm_manager` or empty |
| Token Expiration | `3600` |

**Save → Copy Client ID + Secret**

## ⚙️ Configure Laravel (Step 3)

Edit `admin.yoursite.com/.env`:

```ini
FARMOS_URL=https://farmos.yoursite.com
FARMOS_OAUTH_CLIENT_ID=your_client_id_here
FARMOS_OAUTH_CLIENT_SECRET=your_client_secret_here
FARMOS_OAUTH_SCOPE=farm_manager

# Optional - Direct DB for speed
FARMOS_DB_HOST=127.0.0.1
FARMOS_DB_PORT=3306
FARMOS_DB_DATABASE=farmos_db_name
FARMOS_DB_USERNAME=farmos_user
FARMOS_DB_PASSWORD=farmos_password
```

```bash
cd /path/to/laravel
php artisan config:clear
pkill -f "pool admin.yoursite.com"  # Or restart PHP-FPM
```

## ✅ Test (Step 4)

**Via UI:** `https://admin.yoursite.com/admin/settings` → farmOS section → Test Connection

**Via cURL:**
```bash
curl -X POST 'https://farmos.yoursite.com/oauth/token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'grant_type=client_credentials' \
  --data-urlencode 'client_id=YOUR_CLIENT_ID' \
  --data-urlencode 'client_secret=YOUR_CLIENT_SECRET'
```

✅ Success = `{"token_type":"Bearer","expires_in":3600,"access_token":"eyJ..."}`

## 🚨 Common Issues

| Error | Fix |
|-------|-----|
| `500 server_error` | Keys missing/wrong permissions → Run Step 1 |
| `400 invalid_request` | Check .env variable names: `FARMOS_OAUTH_*` not `FARMOS_CLIENT_*` |
| `Permission denied` | `chown -R www-data:www-data keys/` |
| `Table doesn't exist` | Wrong database name in .env |
| `Password authentication failed` | Check farmOS database credentials |

**Check logs:**
```bash
# farmOS
cd /path/to/farmos
./vendor/bin/drush watchdog:show --type=php --count=5

# Laravel
tail -f storage/logs/laravel.log
```

## 📚 Full Guide

See `FARMOS_OAUTH_SETUP_COMPLETE.md` for detailed troubleshooting and production checklist.
