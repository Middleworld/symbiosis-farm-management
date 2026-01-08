# Centralized Update Server - Implementation Summary

**Date**: January 2026  
**Purpose**: Scalable update distribution for multiple VPS customer installations  
**Status**: Ready for DNS/SSL configuration

## What Was Built

### 1. Update Server API (`/var/www/vhosts/soilsync.shop/update-server/`)

**Core Files:**
- `index.php` - Standalone PHP API with three endpoints:
  - `GET /api/version` - Current version info (public)
  - `GET /api/updates/{version}` - Available updates (requires API key)
  - `GET /api/download/{package}?key={apiKey}` - Download update package (requires API key)

- `config.php` - API keys, rate limiting, security settings
- `version.json` - Current system version (copied from admin)
- `.htaccess` - Apache routing, security headers, HTTPS redirect
- `.gitignore` - Exclude packages and logs from version control

**Directories:**
- `packages/` - Update packages (*.tar.gz + checksums)
- `logs/` - API access logs

**Scripts:**
- `setup-update-server.sh` - Automated Apache/DNS/SSL setup
- `generate-api-key.sh` - Create customer API keys

**Documentation:**
- `README.md` - Full technical documentation
- `QUICKSTART.md` - 5-minute setup guide

### 2. Laravel Integration

**Controller Update:**
- `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/app/Http/Controllers/Admin/SystemUpdateController.php`
  - Changed from direct gold master API to centralized update server
  - Added API key authentication headers
  - Updated response handling for new endpoint structure

**Environment Configuration:**
- `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/.env`
  - Added `UPDATE_SERVER_URL=https://updates.soilsync.shop`
  - Added `UPDATE_SERVER_API_KEY=` (to be populated per customer)

**Admin Interface:**
- `/admin/system/updates` - "Check for Updates" button
- Shows current vs latest version
- Displays available updates and changelog
- Lists plugin versions

### 3. Architecture Design

**Scalability Model:**
```
                    ┌─────────────────────┐
                    │  Update Server      │
                    │  updates.soilsync.  │
                    │  shop:443 (or 8080) │
                    └──────────┬──────────┘
                               │
                ┌──────────────┼──────────────┐
                │              │              │
        ┌───────▼──────┐ ┌────▼─────┐ ┌─────▼──────┐
        │ Customer VPS │ │Customer  │ │ Customer   │
        │ farm1.com    │ │VPS #2    │ │ VPS #N...  │
        └──────────────┘ └──────────┘ └────────────┘
```

**Security Model:**
- API key per customer installation
- HTTPS required for all traffic
- Rate limiting per API key
- SHA256 checksums for package integrity
- No directory listing on packages
- Optional IP whitelisting

## Setup Instructions

### For Gold Master (soilsync.shop)

#### 1. DNS Configuration
Add A record:
```dns
updates.soilsync.shop  A  YOUR_SERVER_IP
```

#### 2. Run Setup Script
```bash
cd /var/www/vhosts/soilsync.shop/update-server
sudo ./setup-update-server.sh
```

**What this does:**
- Creates directory structure (packages/, logs/)
- Sets permissions (www-data:www-data)
- Creates Apache virtual host config
- Enables required modules (rewrite, headers, ssl)
- Reloads Apache

#### 3. Get SSL Certificate
```bash
sudo certbot --apache -d updates.soilsync.shop
```

#### 4. Test API
```bash
curl https://updates.soilsync.shop/api/version
```

Expected output:
```json
{
    "system_version": "1.0.0",
    "release_date": "2026-01-02",
    "release_name": "Gold Master Initial Release",
    ...
}
```

### For Each Customer VPS

#### 1. Generate API Key
On gold master:
```bash
cd /var/www/vhosts/soilsync.shop/update-server
./generate-api-key.sh customer-farm.com
```

Output:
```
API Key Generated
========================================
Customer Domain: customer-farm.com
API Key: a1b2c3d4e5f6...64chars...

Customer Configuration:
UPDATE_SERVER_URL=https://updates.soilsync.shop
UPDATE_SERVER_API_KEY=a1b2c3d4e5f6...64chars...
```

#### 2. Configure Customer .env
On customer VPS, edit Laravel admin `.env`:
```env
UPDATE_SERVER_URL=https://updates.soilsync.shop
UPDATE_SERVER_API_KEY=a1b2c3d4e5f6...64chars...
```

#### 3. Test from Customer
Visit: `https://customer-farm.com/admin/system/updates`  
Click: "Check for Updates"

Should show:
- Current version: 1.0.0
- Latest version: 1.0.0
- "Your system is up to date" (if no updates)

## Creating Updates

### Workflow

1. **Make Changes in Gold Master**
   ```bash
   cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
   # Edit files...
   php artisan test
   ```

2. **Update version.json**
   ```bash
   nano version.json
   # Increment: 1.0.0 → 1.0.1
   # Add changelog entry
   ```

3. **Create Package**
   ```bash
   cd /var/www/vhosts/soilsync.shop/update-server/packages
   mkdir 1.0.1
   cd 1.0.1
   
   # Copy changed files (preserve directory structure)
   cp /path/to/changed/file.php admin.soilsync.shop/app/Services/
   
   # Create manifest.json
   # List files, actions, commands
   
   # Create tarball
   cd ..
   tar -czf 1.0.1.tar.gz 1.0.1/
   sha256sum 1.0.1.tar.gz > 1.0.1.tar.gz.sha256
   ```

4. **Update Server version.json**
   ```bash
   cp /var/www/vhosts/soilsync.shop/admin.soilsync.shop/version.json \
      /var/www/vhosts/soilsync.shop/update-server/version.json
   ```

5. **Test Update Check**
   ```bash
   curl -H "X-API-Key: test-key" \
        https://updates.soilsync.shop/api/updates/1.0.0
   ```

6. **Customers Check for Updates**
   Customers visit `/admin/system/updates`, click "Check for Updates"

## Files Modified/Created

### New Files (11 total)
1. `/var/www/vhosts/soilsync.shop/update-server/index.php` - API endpoint
2. `/var/www/vhosts/soilsync.shop/update-server/config.php` - Configuration
3. `/var/www/vhosts/soilsync.shop/update-server/version.json` - Version info
4. `/var/www/vhosts/soilsync.shop/update-server/.htaccess` - Apache routing
5. `/var/www/vhosts/soilsync.shop/update-server/.gitignore` - Git exclusions
6. `/var/www/vhosts/soilsync.shop/update-server/README.md` - Full docs
7. `/var/www/vhosts/soilsync.shop/update-server/QUICKSTART.md` - Quick guide
8. `/var/www/vhosts/soilsync.shop/update-server/setup-update-server.sh` - Setup script
9. `/var/www/vhosts/soilsync.shop/update-server/generate-api-key.sh` - Key generator
10. `/var/www/vhosts/soilsync.shop/update-server/packages/` - Package directory
11. `/var/www/vhosts/soilsync.shop/update-server/logs/` - Log directory

### Modified Files (2 total)
1. `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/app/Http/Controllers/Admin/SystemUpdateController.php`
   - Changed update URL from `admin.soilsync.shop/api/updates/check` to `updates.soilsync.shop/api/updates/{version}`
   - Added API key authentication
   - Updated response parsing

2. `/var/www/vhosts/soilsync.shop/admin.soilsync.shop/.env`
   - Added `UPDATE_SERVER_URL`
   - Added `UPDATE_SERVER_API_KEY`

## Next Steps

### Immediate (Before Customer Deployment)

1. **Configure DNS**
   - Add A record for updates.soilsync.shop
   - Wait for propagation (5-10 minutes)

2. **Run Setup**
   ```bash
   sudo /var/www/vhosts/soilsync.shop/update-server/setup-update-server.sh
   ```

3. **Get SSL**
   ```bash
   sudo certbot --apache -d updates.soilsync.shop
   ```

4. **Test Endpoints**
   ```bash
   curl https://updates.soilsync.shop/api/version
   # Should return version.json content
   ```

5. **Generate Test API Key**
   ```bash
   cd /var/www/vhosts/soilsync.shop/update-server
   ./generate-api-key.sh test-customer.com
   ```

6. **Test from Gold Master**
   - Add generated API key to gold master .env
   - Visit admin.soilsync.shop/admin/system/updates
   - Click "Check for Updates"
   - Should show: "Your system is up to date"

### Before First Customer Deployment

1. **Create First Update Package** (for testing)
   - Make minor change (e.g., version bump)
   - Create 1.0.1 package with manifest
   - Test customer can see and download update

2. **Document Clone Process**
   - Use prepare-clone.sh script
   - Test on fresh VPS or Docker container
   - Update documentation with any missing steps

3. **Create Customer Onboarding Checklist**
   - DNS setup
   - SSL certificates
   - Database import
   - .env configuration (including UPDATE_SERVER_API_KEY)
   - Test login, test update check

### Ongoing Maintenance

**Weekly:**
- Check disk space in packages/
- Review access logs for unusual activity
- Verify SSL certificate expiry date

**Monthly:**
- Clean up old packages (keep last 5 versions)
- Rotate logs
- Test update process end-to-end

**Before Each Release:**
1. Test in gold master
2. Create update package
3. Test on staging customer (or test environment)
4. Monitor first few customer updates
5. Document any issues

## Key Design Decisions

### Why Separate Update Server?
**User requirement**: "what woukd happen in the futuer if was sold a lot of paid hosting. So had to take on a second bigger VPS?"

**Solution**: Centralized update server serves unlimited customer installations:
- One source of truth for updates
- No dependency on gold master admin being accessible
- Can scale to CDN if needed for bandwidth
- Below document root, own subdomain/port for isolation
- Pure PHP, no Laravel overhead

### Why API Keys Instead of OAuth?
- Simpler for customer configuration
- No token refresh complexity
- Easy to generate and revoke per customer
- Sufficient security with HTTPS + rate limiting

### Why Manual Updates Instead of Auto-Update?
- Farm businesses need control over changes
- Testing period before applying updates
- Can schedule updates during off-hours
- Reduces risk of breaking production systems

## Monitoring

### Check Update Server Health
```bash
# Test version endpoint
curl https://updates.soilsync.shop/api/version | jq

# Check Apache status
sudo systemctl status apache2

# Recent access logs
tail -f /var/log/apache2/updates.soilsync.shop-access.log

# Recent errors
tail -f /var/log/apache2/updates.soilsync.shop-error.log

# Disk space
df -h
du -sh /var/www/vhosts/soilsync.shop/update-server/packages
```

### Track Customer Updates
```bash
# Count update checks by IP
awk '{print $1}' /var/log/apache2/updates.soilsync.shop-access.log | \
  grep "/api/updates" | sort | uniq -c

# Most downloaded versions
grep "/api/download/" /var/log/apache2/updates.soilsync.shop-access.log | \
  awk '{print $7}' | sort | uniq -c
```

## Support Resources

- **QUICKSTART.md** - 5-minute setup guide
- **README.md** - Full technical documentation
- **setup-update-server.sh** - Automated setup
- **generate-api-key.sh** - Customer API key generation
- **/var/log/apache2/updates.soilsync.shop-*.log** - Troubleshooting logs

## Success Criteria

Update server is **production ready** when:

- ✅ Code written and tested
- ⏳ DNS configured (updates.soilsync.shop → server IP)
- ⏳ SSL certificate obtained
- ⏳ Apache virtual host active
- ⏳ `/api/version` endpoint responds
- ⏳ Test API key generated
- ⏳ Customer can check for updates successfully
- ⏳ First update package created and downloadable

**Current Status**: Code complete, ready for DNS/SSL configuration.

## Deployment Checklist

```bash
# 1. DNS Setup
# Add A record: updates.soilsync.shop → YOUR_IP
# Verify: nslookup updates.soilsync.shop

# 2. Run Setup
cd /var/www/vhosts/soilsync.shop/update-server
sudo ./setup-update-server.sh

# 3. SSL Certificate
sudo certbot --apache -d updates.soilsync.shop

# 4. Firewall (if needed)
sudo ufw allow 443/tcp
sudo ufw reload

# 5. Test
curl https://updates.soilsync.shop/api/version

# 6. Generate Test Key
./generate-api-key.sh test-customer.com

# 7. Configure Gold Master
# Add UPDATE_SERVER_API_KEY to .env

# 8. Test Update Check
# Visit admin.soilsync.shop/admin/system/updates
# Click "Check for Updates"

# ✓ Success: "Your system is up to date" message
```

---

**Implemented**: January 2026  
**Version**: 1.0.0  
**Ready for**: DNS configuration and SSL setup
