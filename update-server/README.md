# Middle World Farms - Centralized Update Server

## Purpose
Standalone API server that distributes software updates to all customer VPS installations. Runs on its own subdomain/port to serve multiple customer sites from a single source.

## Architecture
- **URL**: `https://updates.soilsync.shop:8080` (or standard port with subdomain)
- **Location**: `/var/www/vhosts/soilsync.shop/update-server/`
- **Purpose**: Centralized update distribution for multi-VPS scalability
- **Security**: API key authentication, SSL required

## Endpoints

### GET /api/version
Returns current gold master version information.

**Response**:
```json
{
    "system_version": "1.0.0",
    "release_date": "2026-01-02",
    "release_name": "Gold Master Initial Release",
    "components": {
        "laravel_admin": "1.0.0",
        "wordpress_core": "6.4.2",
        "plugins": {
            "mwf-integration": "1.0.0",
            "mwf-solidarity-pricing": "1.0.0",
            "mwf-sso": "1.0.0",
            "mwf-subscriptions": "1.0.0",
            "mwf-team-members": "1.0.0",
            "mwf-reviews": "1.0.0"
        }
    }
}
```

### GET /api/updates/{currentVersion}
Lists available updates for a specific version.

**Example**: `/api/updates/1.0.0`

**Response**:
```json
{
    "updates_available": true,
    "latest_version": "1.0.1",
    "updates": [
        {
            "version": "1.0.1",
            "release_date": "2026-01-10",
            "changelog": "Bug fixes and improvements",
            "package_url": "/api/download/1.0.1",
            "package_size": "2.5MB",
            "checksum": "sha256:abc123..."
        }
    ]
}
```

### GET /api/download/{version}?key={apiKey}
Downloads update package (requires authentication).

**Example**: `/api/download/1.0.1?key=customer-api-key`

**Response**: Binary package file (tar.gz)

## Setup Instructions

### 1. Configure Subdomain

**Option A: Subdomain with Standard Port (Recommended)**
```bash
# Create virtual host config
sudo nano /etc/apache2/sites-available/updates.soilsync.shop.conf
```

```apache
<VirtualHost *:443>
    ServerName updates.soilsync.shop
    DocumentRoot /var/www/vhosts/soilsync.shop/update-server

    <Directory /var/www/vhosts/soilsync.shop/update-server>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/key.pem
    SSLCertificateChainFile /path/to/ssl/chain.pem

    ErrorLog ${APACHE_LOG_DIR}/updates-error.log
    CustomLog ${APACHE_LOG_DIR}/updates-access.log combined
</VirtualHost>
```

**Enable site**:
```bash
sudo a2ensite updates.soilsync.shop
sudo systemctl reload apache2
```

**Option B: Custom Port (8080)**
```bash
# Add Listen directive to Apache
sudo nano /etc/apache2/ports.conf
# Add: Listen 8080

# Create virtual host
sudo nano /etc/apache2/sites-available/updates-8080.conf
```

```apache
<VirtualHost *:8080>
    ServerName updates.soilsync.shop
    DocumentRoot /var/www/vhosts/soilsync.shop/update-server

    <Directory /var/www/vhosts/soilsync.shop/update-server>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # SSL configuration for port 8080
    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/key.pem
    SSLCertificateChainFile /path/to/ssl/chain.pem

    ErrorLog ${APACHE_LOG_DIR}/updates-8080-error.log
    CustomLog ${APACHE_LOG_DIR}/updates-8080-access.log combined
</VirtualHost>
```

### 2. Configure DNS
Add DNS A record:
```
updates.soilsync.shop  A  YOUR_SERVER_IP
```

### 3. Get SSL Certificate
```bash
# If using Let's Encrypt
sudo certbot --apache -d updates.soilsync.shop
```

### 4. Create .htaccess for API Routing
```bash
# Already created automatically by update-server/index.php setup
# Redirects all requests to index.php for clean URLs
```

### 5. Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/vhosts/soilsync.shop/update-server
sudo chmod -R 755 /var/www/vhosts/soilsync.shop/update-server
sudo chmod 750 /var/www/vhosts/soilsync.shop/update-server/packages
```

### 6. Test Endpoints
```bash
# Test version endpoint
curl https://updates.soilsync.shop/api/version

# Should return:
# {"system_version":"1.0.0","release_date":"2026-01-02",...}

# Test updates endpoint
curl https://updates.soilsync.shop/api/updates/1.0.0

# Test download (with API key)
curl -O https://updates.soilsync.shop/api/download/1.0.1?key=test-key
```

## Creating Update Packages

### 1. Use Package Script
```bash
cd /var/www/vhosts/soilsync.shop/scripts/deployment
./package-update.sh 1.0.1 "Bug fixes and improvements"
```

### 2. Manual Package Creation
```bash
cd /var/www/vhosts/soilsync.shop/update-server/packages

# Create directory for version
mkdir 1.0.1
cd 1.0.1

# Copy updated files
cp /path/to/updated/file.php .

# Create manifest
cat > manifest.json << EOF
{
    "version": "1.0.1",
    "files": [
        {"path": "admin.soilsync.shop/app/Http/Controllers/Admin/ProductController.php", "action": "replace"},
        {"path": "admin.soilsync.shop/app/Services/WooCommerceApiService.php", "action": "replace"}
    ],
    "commands": [
        "php artisan config:clear",
        "php artisan cache:clear"
    ]
}
EOF

# Create tarball
cd ..
tar -czf 1.0.1.tar.gz 1.0.1/

# Generate checksum
sha256sum 1.0.1.tar.gz > 1.0.1.tar.gz.sha256
```

### 3. Update version.json
```bash
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop
nano version.json
# Increment version, add changelog entry
```

## API Key Management

### Generate Customer API Keys
```bash
# Simple approach: Generate random keys
openssl rand -hex 32

# Store in database or config file
# Associate with customer domain/installation
```

### Validate Keys (in index.php)
```php
$validKeys = [
    'customer1-key' => 'customer1-farm.com',
    'customer2-key' => 'customer2-farm.com',
];

if (!isset($validKeys[$apiKey])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}
```

## Customer Side Integration

### In Laravel Admin (SystemUpdateController)
```php
public function checkForUpdates()
{
    $currentVersion = config('app.version');
    $updateServerUrl = 'https://updates.soilsync.shop';
    $apiKey = config('services.update_server.api_key');

    $response = Http::get("{$updateServerUrl}/api/updates/{$currentVersion}", [
        'key' => $apiKey
    ]);

    if ($response->successful()) {
        $data = $response->json();
        return view('admin.system.updates', compact('data'));
    }

    return back()->with('error', 'Could not check for updates');
}
```

### In .env (Customer Site)
```env
UPDATE_SERVER_URL=https://updates.soilsync.shop
UPDATE_SERVER_API_KEY=customer-specific-key-here
```

## Security Best Practices

1. **SSL Required**: Always use HTTPS for update downloads
2. **API Key Authentication**: Validate keys before serving updates
3. **Rate Limiting**: Prevent abuse with request limits
4. **Checksum Verification**: Verify package integrity after download
5. **Directory Listing Off**: Prevent browsing packages directory
6. **Firewall Rules**: Restrict access to known customer IPs (optional)

## Monitoring

### Log Files
- Apache access: `/var/log/apache2/updates-access.log`
- Apache errors: `/var/log/apache2/updates-error.log`

### Track Downloads
```bash
# Count downloads by version
grep "GET /api/download" /var/log/apache2/updates-access.log | awk '{print $7}' | sort | uniq -c

# Monitor recent activity
tail -f /var/log/apache2/updates-access.log
```

## Scalability

### Load Balancing (Future)
If serving many customers:
- Use CDN for package distribution (CloudFlare, AWS CloudFront)
- Set up multiple update server mirrors
- Implement geographic routing

### Package Storage
- Small deployments: Local filesystem
- Large scale: Object storage (S3, DigitalOcean Spaces)

## Troubleshooting

### Update Server Not Accessible
```bash
# Check Apache is running
sudo systemctl status apache2

# Check port is open
sudo netstat -tlnp | grep :8080  # If using custom port

# Check firewall
sudo ufw status
sudo ufw allow 8080/tcp  # If using custom port
```

### SSL Certificate Issues
```bash
# Test SSL
openssl s_client -connect updates.soilsync.shop:443

# Renew Let's Encrypt
sudo certbot renew
```

### Permissions Issues
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/vhosts/soilsync.shop/update-server

# Fix directory permissions
find /var/www/vhosts/soilsync.shop/update-server -type d -exec chmod 755 {} \;

# Fix file permissions
find /var/www/vhosts/soilsync.shop/update-server -type f -exec chmod 644 {} \;
```

## Maintenance

### Regular Tasks
1. Monitor disk space (packages directory grows over time)
2. Clean up old package versions (keep last 3-5 versions)
3. Review access logs for unusual activity
4. Test update process before releasing new versions
5. Keep SSL certificates renewed

### Version Cleanup
```bash
# Remove packages older than 6 months
find /var/www/vhosts/soilsync.shop/update-server/packages -name "*.tar.gz" -mtime +180 -delete
```

## Notes
- Update server is separate from main application (no Laravel dependencies)
- Designed for multiple VPS scalability ("if was sold a lot of paid hosting")
- Below document root, can run on own port for isolation
- Single source of truth for all customer installations
