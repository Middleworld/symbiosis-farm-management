# Centralized Update Server - Quick Start Guide

## Overview
The update server is a standalone API that serves software updates to all customer VPS installations. It's designed for scalability when "sold a lot of paid hosting" - multiple customer servers can check for updates from this single centralized source.

## File Structure
```
/var/www/vhosts/soilsync.shop/update-server/
├── index.php                    # Main API endpoint
├── config.php                   # API keys and settings
├── version.json                 # Current version info (copied from admin)
├── .htaccess                    # Apache routing and security
├── .gitignore                   # Exclude packages/logs from git
├── README.md                    # Full documentation
├── setup-update-server.sh       # Automated setup script
├── generate-api-key.sh          # Generate customer API keys
├── packages/                    # Update packages (*.tar.gz)
│   ├── 1.0.1.tar.gz
│   ├── 1.0.1.tar.gz.sha256
│   └── ...
└── logs/                        # Access logs
    └── access.log
```

## Quick Setup (5 minutes)

### 1. Run Setup Script
```bash
cd /var/www/vhosts/soilsync.shop/update-server
sudo ./setup-update-server.sh
```

This will:
- Create directory structure
- Set permissions
- Configure Apache virtual host
- Enable required modules
- Reload Apache

### 2. Configure DNS
Add DNS A record:
```
updates.soilsync.shop  A  YOUR_SERVER_IP
```

Wait for DNS propagation (1-5 minutes):
```bash
nslookup updates.soilsync.shop
```

### 3. Get SSL Certificate
```bash
sudo certbot --apache -d updates.soilsync.shop
```

After obtaining certificate, verify SSL is working:
```bash
curl https://updates.soilsync.shop/api/version
```

### 4. Generate Customer API Keys
```bash
cd /var/www/vhosts/soilsync.shop/update-server
./generate-api-key.sh customer-farm.com
```

This will:
- Generate secure 64-character API key
- Add to config.php automatically
- Display customer .env configuration

### 5. Test the API
```bash
# Test version endpoint
curl https://updates.soilsync.shop/api/version

# Expected output:
# {"system_version":"1.0.0","release_date":"2026-01-02",...}

# Test updates endpoint (with API key)
curl -H "X-API-Key: your-api-key-here" \
     https://updates.soilsync.shop/api/updates/1.0.0

# Expected output:
# {"updates_available":false,"latest_version":"1.0.0",...}
```

## Customer Configuration

### On Customer VPS
Add to Laravel admin `.env`:
```env
UPDATE_SERVER_URL=https://updates.soilsync.shop
UPDATE_SERVER_API_KEY=customer-specific-key-here
```

### Test from Customer Side
Visit: `https://customer-farm.com/admin/system/updates`
Click: "Check for Updates"

Should see:
- Current version: 1.0.0
- Latest version: 1.0.0
- No updates available (or list of updates if available)

## Creating an Update Package

### 1. Make Code Changes
Edit files in gold master (soilsync.shop):
```bash
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
# Make your changes...
```

### 2. Test Changes
```bash
php artisan test
# Visit admin.soilsync.shop and verify changes work
```

### 3. Update version.json
```bash
nano version.json
```

Increment version (1.0.0 → 1.0.1):
```json
{
    "system_version": "1.0.1",
    "release_date": "2026-01-10",
    "release_name": "Bug Fixes",
    "changelog": [
        {
            "version": "1.0.1",
            "date": "2026-01-10",
            "changes": [
                "Fixed short description sync",
                "Added SKU conflict handling"
            ]
        }
    ]
}
```

### 4. Create Update Package
```bash
cd /var/www/vhosts/soilsync.shop/update-server/packages
mkdir 1.0.1
cd 1.0.1

# Copy changed files
cp /var/www/vhosts/soilsync.shop/admin.soilsync.shop/app/Services/WooCommerceApiService.php \
   admin.soilsync.shop/app/Services/

# Create manifest
cat > manifest.json << 'EOF'
{
    "version": "1.0.1",
    "release_date": "2026-01-10",
    "description": "Bug fixes and improvements",
    "files": [
        {
            "path": "admin.soilsync.shop/app/Services/WooCommerceApiService.php",
            "action": "replace"
        }
    ],
    "commands": [
        "php artisan config:clear",
        "php artisan cache:clear"
    ],
    "backup_required": true
}
EOF

# Create tarball
cd ..
tar -czf 1.0.1.tar.gz 1.0.1/

# Generate checksum
sha256sum 1.0.1.tar.gz > 1.0.1.tar.gz.sha256
```

### 5. Update Server version.json
```bash
cp /var/www/vhosts/soilsync.shop/admin.soilsync.shop/version.json \
   /var/www/vhosts/soilsync.shop/update-server/version.json
```

### 6. Test Update Package
```bash
# From customer VPS (or test environment)
curl -H "X-API-Key: test-key" \
     https://updates.soilsync.shop/api/updates/1.0.0

# Should show:
# "updates_available": true,
# "latest_version": "1.0.1"
```

## Architecture Notes

### Why Separate Update Server?
- **Scalability**: Serve 10, 100, or 1000+ customer installations from one source
- **Security**: Isolated from main application, below document root
- **Performance**: No Laravel overhead, pure PHP API
- **Independence**: Customers don't need access to gold master admin
- **Bandwidth**: Can move to CDN if needed

### Port Options
**Standard HTTPS (Port 443) - Recommended:**
- URL: `https://updates.soilsync.shop`
- Subdomain on standard port
- Works with standard SSL certificates
- No firewall issues

**Custom Port (e.g., 8080):**
- URL: `https://updates.soilsync.shop:8080`
- More isolated
- May require firewall rules
- Some networks block non-standard ports

### Security Model
- **API Keys**: Each customer has unique key
- **HTTPS Required**: All traffic encrypted
- **Rate Limiting**: Prevent abuse
- **Checksum Verification**: Ensure package integrity
- **No Directory Listing**: Hide packages directory
- **IP Whitelisting**: Optional, for high-security deployments

## Monitoring

### Check Access Logs
```bash
# Recent activity
tail -f /var/log/apache2/updates.soilsync.shop-access.log

# Count requests by customer
awk '{print $1}' /var/log/apache2/updates.soilsync.shop-access.log | sort | uniq -c

# Most downloaded versions
grep "/api/download/" /var/log/apache2/updates.soilsync.shop-access.log | \
  awk '{print $7}' | sort | uniq -c
```

### Check Error Logs
```bash
tail -f /var/log/apache2/updates.soilsync.shop-error.log
```

### Disk Space
```bash
# Check package directory size
du -sh /var/www/vhosts/soilsync.shop/update-server/packages

# List packages by size
ls -lh /var/www/vhosts/soilsync.shop/update-server/packages/*.tar.gz
```

## Troubleshooting

### "Connection refused"
```bash
# Check Apache is running
sudo systemctl status apache2

# Check if port is open
sudo netstat -tlnp | grep :443

# Check DNS resolution
nslookup updates.soilsync.shop

# Check firewall
sudo ufw status
```

### "Invalid API key"
```bash
# List configured keys
php -r "print_r(require('/var/www/vhosts/soilsync.shop/update-server/config.php')['api_keys']);"

# Generate new key
./generate-api-key.sh new-customer.com
```

### "SSL certificate problem"
```bash
# Check certificate
openssl s_client -connect updates.soilsync.shop:443

# Renew certificate
sudo certbot renew
```

### "Package not found"
```bash
# List available packages
ls -la /var/www/vhosts/soilsync.shop/update-server/packages

# Check permissions
ls -la /var/www/vhosts/soilsync.shop/update-server
```

## Maintenance

### Weekly
- Check disk space
- Review access logs for unusual activity
- Verify SSL certificate expiry date

### Monthly
- Clean up old packages (keep last 5 versions)
- Review and rotate logs
- Test update process from customer perspective

### Before Each Release
1. Test changes in gold master (admin.soilsync.shop)
2. Create update package
3. Test update package on staging customer
4. Monitor first few customer updates
5. Document any issues in changelog

## Next Steps

1. **Configure DNS** for updates.soilsync.shop
2. **Run setup script**: `sudo ./setup-update-server.sh`
3. **Get SSL certificate**: `sudo certbot --apache -d updates.soilsync.shop`
4. **Generate test API key**: `./generate-api-key.sh test-customer.com`
5. **Test endpoints**: `curl https://updates.soilsync.shop/api/version`
6. **Configure customer**: Add UPDATE_SERVER_URL and UPDATE_SERVER_API_KEY to .env
7. **Create first update package**: Follow "Creating an Update Package" section

## Support

For issues or questions:
- Review `/var/log/apache2/updates.soilsync.shop-error.log`
- Check update-server/README.md for detailed documentation
- Test endpoints with curl before customer rollout
