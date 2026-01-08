# Update Server Deployment Checklist

**Purpose**: Step-by-step guide to get the centralized update server live  
**Time Required**: ~15-20 minutes  
**Status**: Code complete, ready for deployment

---

## Pre-Flight Check

- [x] Update server code created (`/var/www/vhosts/soilsync.shop/update-server/`)
- [x] SystemUpdateController updated with API support
- [x] .env configured with UPDATE_SERVER_URL
- [x] Documentation complete (README.md, QUICKSTART.md, ARCHITECTURE.md)
- [ ] DNS configured
- [ ] Apache virtual host configured
- [ ] SSL certificate obtained
- [ ] API key generated for testing
- [ ] Endpoints tested

---

## Step 1: DNS Configuration (2 minutes)

### 1.1 Add DNS A Record

**Provider**: Your DNS provider (e.g., Cloudflare, NameCheap, etc.)

**Record Type**: A  
**Name**: `updates` (or `updates.soilsync.shop`)  
**Value/IP**: Your server IP address  
**TTL**: 300 (5 minutes) or Auto

**Example**:
```
Type  Name     Value           TTL
A     updates  YOUR_SERVER_IP  300
```

### 1.2 Verify DNS Propagation

```bash
# Wait 1-5 minutes, then check:
nslookup updates.soilsync.shop

# Should show:
# Name: updates.soilsync.shop
# Address: YOUR_SERVER_IP
```

**Status**: [ ] Complete

---

## Step 2: Run Setup Script (3 minutes)

### 2.1 Execute Setup

```bash
cd /var/www/vhosts/soilsync.shop/update-server
sudo ./setup-update-server.sh
```

**What this does**:
- Creates directory structure
- Sets permissions (www-data:www-data)
- Creates Apache virtual host config
- Enables required modules (rewrite, headers, ssl)
- Reloads Apache

**Expected output**:
```
[1/8] Creating directory structure... ✓
[2/8] Setting permissions... ✓
[3/8] Using standard port 443
[4/8] Creating Apache virtual host... ✓
[5/8] Enabling required Apache modules... ✓
[6/8] Enabling site... ✓
[7/8] Testing Apache configuration... ✓
[8/8] Reloading Apache... ✓

Setup Complete!
```

**Status**: [ ] Complete

---

## Step 3: SSL Certificate (5 minutes)

### 3.1 Obtain Certificate with Certbot

```bash
sudo certbot --apache -d updates.soilsync.shop
```

**Prompts**:
- Email address: (your email)
- Terms of service: Y
- Email list: N (optional)

**Expected output**:
```
Successfully received certificate.
Certificate is saved at: /etc/letsencrypt/live/updates.soilsync.shop/fullchain.pem
Key is saved at: /etc/letsencrypt/live/updates.soilsync.shop/privkey.pem
This certificate expires on 2026-04-XX.
Congratulations! ...
```

### 3.2 Verify SSL

```bash
# Test HTTPS connection
curl -I https://updates.soilsync.shop/api/version

# Should return:
# HTTP/2 200
# content-type: application/json
```

**Status**: [ ] Complete

---

## Step 4: Configure Firewall (1 minute)

### 4.1 Allow HTTPS Traffic (if needed)

```bash
# Check firewall status
sudo ufw status

# If firewall is active and 443 is not allowed:
sudo ufw allow 443/tcp
sudo ufw reload

# Verify:
sudo ufw status | grep 443
```

**Expected**:
```
443/tcp                    ALLOW       Anywhere
```

**Status**: [ ] Complete

---

## Step 5: Test API Endpoints (3 minutes)

### 5.1 Test Version Endpoint (Public)

```bash
curl https://updates.soilsync.shop/api/version | jq
```

**Expected**:
```json
{
  "system_version": "1.0.0",
  "release_date": "2026-01-02",
  "release_name": "Gold Master Initial Release",
  "components": {
    "laravel_admin": {
      "version": "1.0.0"
    }
  }
}
```

**Status**: [ ] Complete

### 5.2 Test Without API Key (Should Fail)

```bash
curl -I https://updates.soilsync.shop/api/updates/1.0.0
```

**Expected**:
```
HTTP/2 403
```

**Status**: [ ] Complete

---

## Step 6: Generate Test API Key (2 minutes)

### 6.1 Generate Key

```bash
cd /var/www/vhosts/soilsync.shop/update-server
./generate-api-key.sh test-customer.com
```

**Expected output**:
```
========================================
API Key Generated
========================================
Customer Domain: test-customer.com
API Key: abc123...64chars...

Customer Configuration:
UPDATE_SERVER_URL=https://updates.soilsync.shop
UPDATE_SERVER_API_KEY=abc123...64chars...

✓ Configuration updated
```

**IMPORTANT**: Copy the API key, you'll need it in the next step!

**Status**: [ ] Complete  
**API Key**: `_________________________________________________`

---

## Step 7: Configure Gold Master (2 minutes)

### 7.1 Update .env File

```bash
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
nano .env
```

**Find this section** (at the bottom):
```env
# Update Server Configuration
UPDATE_SERVER_URL=https://updates.soilsync.shop
UPDATE_SERVER_API_KEY=
```

**Replace with**:
```env
# Update Server Configuration
UPDATE_SERVER_URL=https://updates.soilsync.shop
UPDATE_SERVER_API_KEY=abc123...64chars...  # Paste your API key here
```

**Save**: Ctrl+O, Enter, Ctrl+X

### 7.2 Clear Config Cache

```bash
php artisan config:clear
```

**Status**: [ ] Complete

---

## Step 8: Test from Laravel Admin UI (3 minutes)

### 8.1 Visit Updates Page

**URL**: https://admin.soilsync.shop/admin/system/updates

**Expected**: Page loads showing:
- Current Version: 1.0.0
- Latest Version: (not checked yet)
- "Check for Updates" button

**Status**: [ ] Complete

### 8.2 Click "Check for Updates"

**Expected response**:
- ✅ "Your system is up to date"
- Current Version: 1.0.0
- Latest Version: 1.0.0
- No updates available

**OR** (if error):
- ❌ Connection error message
- Check API key is correct
- Check firewall allows HTTPS

**Status**: [ ] Complete

---

## Step 9: Test with API Key in cURL (2 minutes)

### 9.1 Test Updates Endpoint

```bash
curl -H "X-API-Key: YOUR_API_KEY_HERE" \
     https://updates.soilsync.shop/api/updates/1.0.0 | jq
```

**Expected**:
```json
{
  "updates_available": false,
  "latest_version": "1.0.0",
  "updates": []
}
```

**Status**: [ ] Complete

---

## Step 10: Monitor Logs (Ongoing)

### 10.1 Check Access Logs

```bash
# Watch real-time access
tail -f /var/log/apache2/updates.soilsync.shop-access.log

# Should show your test requests
```

### 10.2 Check Error Logs

```bash
# Check for any errors
tail -f /var/log/apache2/updates.soilsync.shop-error.log

# Should be empty (no errors)
```

**Status**: [ ] Complete

---

## Troubleshooting

### Issue: DNS not resolving

**Symptom**: `nslookup updates.soilsync.shop` returns no result

**Fix**:
1. Wait 5-10 minutes for DNS propagation
2. Check DNS provider settings
3. Try `dig updates.soilsync.shop` for more details

---

### Issue: Apache virtual host not working

**Symptom**: `curl` returns connection refused

**Fix**:
```bash
# Check Apache is running
sudo systemctl status apache2

# Check virtual host is enabled
ls -la /etc/apache2/sites-enabled/ | grep updates

# Check Apache config
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

---

### Issue: SSL certificate failed

**Symptom**: Certbot returns error

**Fix**:
```bash
# Make sure DNS is resolving first
nslookup updates.soilsync.shop

# Check port 80 is open (needed for Let's Encrypt validation)
sudo ufw allow 80/tcp
sudo ufw reload

# Try again
sudo certbot --apache -d updates.soilsync.shop
```

---

### Issue: API returns 403 Forbidden

**Symptom**: `curl` returns HTTP/2 403

**Fix**:
```bash
# Check API key is in config.php
php -r "print_r(require('/var/www/vhosts/soilsync.shop/update-server/config.php')['api_keys']);"

# Regenerate API key if needed
cd /var/www/vhosts/soilsync.shop/update-server
./generate-api-key.sh test-customer.com

# Update .env with new key
nano /var/www/vhosts/soilsync.shop/admin.soilsync.shop/.env
php artisan config:clear
```

---

### Issue: Update check fails in Laravel admin

**Symptom**: "Could not connect to update server" message

**Fix**:
```bash
# Check .env has correct configuration
grep UPDATE_SERVER /var/www/vhosts/soilsync.shop/admin.soilsync.shop/.env

# Should show:
# UPDATE_SERVER_URL=https://updates.soilsync.shop
# UPDATE_SERVER_API_KEY=abc123...

# Clear config cache
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
php artisan config:clear

# Check Laravel logs
tail -f storage/logs/laravel.log
```

---

## Success Criteria

**Update server is fully operational when:**

- [x] Code deployed to `/var/www/vhosts/soilsync.shop/update-server/`
- [ ] DNS resolves `updates.soilsync.shop` to server IP
- [ ] Apache serves requests on port 443
- [ ] SSL certificate valid and active
- [ ] `/api/version` returns JSON (public access)
- [ ] `/api/updates/{version}` requires API key (returns 403 without)
- [ ] `/api/updates/{version}` returns update info with valid API key
- [ ] Laravel admin can check for updates successfully
- [ ] Logs show requests in `/var/log/apache2/updates.soilsync.shop-access.log`

**Current Progress**: [  /  ] Complete

---

## Next Steps After Deployment

### 1. Create First Update Package

**Purpose**: Test the full update workflow

```bash
cd /var/www/vhosts/soilsync.shop/update-server/packages
mkdir 1.0.1
cd 1.0.1

# Create test manifest
cat > manifest.json << 'EOF'
{
    "version": "1.0.1",
    "release_date": "2026-01-10",
    "description": "Test update - minor version bump",
    "files": [],
    "commands": [],
    "backup_required": false
}
EOF

# Create tarball
cd ..
tar -czf 1.0.1.tar.gz 1.0.1/
sha256sum 1.0.1.tar.gz > 1.0.1.tar.gz.sha256

# Update version.json
nano /var/www/vhosts/soilsync.shop/admin.soilsync.shop/version.json
# Change: "system_version": "1.0.1"

# Copy to update server
cp /var/www/vhosts/soilsync.shop/admin.soilsync.shop/version.json \
   /var/www/vhosts/soilsync.shop/update-server/version.json

# Test update check
curl -H "X-API-Key: YOUR_KEY" \
     https://updates.soilsync.shop/api/updates/1.0.0 | jq

# Should show: "updates_available": true
```

### 2. Generate Customer API Keys

**When deploying to first customer**:

```bash
cd /var/www/vhosts/soilsync.shop/update-server
./generate-api-key.sh customer-domain.com

# Give customer the UPDATE_SERVER_API_KEY for their .env
```

### 3. Document Customer Onboarding

Add to customer setup documentation:
- UPDATE_SERVER_URL configuration
- UPDATE_SERVER_API_KEY setup
- How to check for updates
- What to do when updates are available

### 4. Set Up Monitoring

**Recommended tools**:
- UptimeRobot (free) - Monitor uptime of updates.soilsync.shop
- Cronitor - Monitor update check frequency
- Weekly review of access logs

---

## Reference Documentation

**File Locations**:
- Update Server: `/var/www/vhosts/soilsync.shop/update-server/`
- Quick Start: `QUICKSTART.md`
- Full Documentation: `README.md`
- Architecture: `ARCHITECTURE.md`
- Implementation Summary: `IMPLEMENTATION_SUMMARY.md`

**Scripts**:
- Setup: `./setup-update-server.sh`
- Generate Keys: `./generate-api-key.sh`
- Create Package: (See README.md)

**Logs**:
- Access: `/var/log/apache2/updates.soilsync.shop-access.log`
- Errors: `/var/log/apache2/updates.soilsync.shop-error.log`
- Laravel: `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/storage/logs/laravel.log`

---

## Support

**If you get stuck**:
1. Check the troubleshooting section above
2. Review logs for error messages
3. Verify each step was completed successfully
4. Test with `curl` before testing in Laravel admin

**Common first-time issues**:
- DNS not propagated yet (wait 5-10 minutes)
- Firewall blocking port 443 (check with `sudo ufw status`)
- API key typo in .env (regenerate if unsure)
- Apache not reloaded after config changes (run `sudo systemctl reload apache2`)

---

**Deployment Checklist Version**: 1.0  
**Last Updated**: January 2026  
**Estimated Time**: 15-20 minutes  
**Difficulty**: Beginner (mostly automated by scripts)

---

## Final Check

**Before declaring success, verify**:

1. [ ] `curl https://updates.soilsync.shop/api/version` works
2. [ ] Laravel admin shows "Check for Updates" button
3. [ ] Clicking "Check for Updates" shows "Your system is up to date"
4. [ ] Logs show the request in `/var/log/apache2/updates.soilsync.shop-access.log`
5. [ ] No errors in `/var/log/apache2/updates.soilsync.shop-error.log`

**If all 5 checks pass**: ✅ Update server is live and operational!

**Ready to serve**: Unlimited customer installations 🚀
