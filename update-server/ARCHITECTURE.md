# Update Server Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    GOLD MASTER SERVER                           │
│                    soilsync.shop                                │
│                                                                 │
│  ┌──────────────────────┐     ┌────────────────────────────┐  │
│  │ Laravel Admin        │     │ Update Server              │  │
│  │ admin.soilsync.shop  │────▶│ updates.soilsync.shop:443  │  │
│  │                      │     │                            │  │
│  │ - Products           │     │ Endpoints:                 │  │
│  │ - Orders             │     │ - GET /api/version         │  │
│  │ - Customers          │     │ - GET /api/updates/{v}     │  │
│  │ - Settings           │     │ - GET /api/download/{pkg}  │  │
│  │                      │     │                            │  │
│  │ System Updates Page: │     │ Stores:                    │  │
│  │ "Check for Updates"  │     │ - version.json             │  │
│  └──────────────────────┘     │ - packages/*.tar.gz        │  │
│                                │ - API keys (config.php)    │  │
│                                └────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                      │
                                      │ HTTPS + API Key
                                      │
              ┌───────────────────────┼───────────────────────┐
              │                       │                       │
              ▼                       ▼                       ▼
    ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐
    │ Customer VPS #1  │    │ Customer VPS #2  │    │ Customer VPS #N  │
    │ farm1.com        │    │ organic-farm.net │    │ csa-delivery.io  │
    │                  │    │                  │    │                  │
    │ Laravel Admin    │    │ Laravel Admin    │    │ Laravel Admin    │
    │ Checks for       │    │ Checks for       │    │ Checks for       │
    │ updates via      │    │ updates via      │    │ updates via      │
    │ unique API key   │    │ unique API key   │    │ unique API key   │
    │                  │    │                  │    │                  │
    │ Downloads and    │    │ Downloads and    │    │ Downloads and    │
    │ applies updates  │    │ applies updates  │    │ applies updates  │
    └──────────────────┘    └──────────────────┘    └──────────────────┘
```

## Update Flow

```
1. DEVELOPMENT (Gold Master)
   ┌─────────────────────────────────────────┐
   │ Developer makes changes in gold master  │
   │ - Edit code in admin.soilsync.shop      │
   │ - Test changes locally                  │
   │ - Update version.json (1.0.0 → 1.0.1)   │
   └─────────────────┬───────────────────────┘
                     │
                     ▼
2. PACKAGE CREATION
   ┌─────────────────────────────────────────┐
   │ Create update package                    │
   │ - Copy changed files to packages/1.0.1/  │
   │ - Create manifest.json                   │
   │ - tar -czf 1.0.1.tar.gz 1.0.1/          │
   │ - sha256sum 1.0.1.tar.gz                │
   │ - Copy version.json to update server    │
   └─────────────────┬───────────────────────┘
                     │
                     ▼
3. CUSTOMER CHECK
   ┌─────────────────────────────────────────┐
   │ Customer visits /admin/system/updates   │
   │ Clicks "Check for Updates"              │
   │                                         │
   │ SystemUpdateController:                 │
   │ - GET updates.soilsync.shop/api/        │
   │       updates/1.0.0?key={apiKey}        │
   └─────────────────┬───────────────────────┘
                     │
                     ▼
4. UPDATE SERVER RESPONSE
   ┌─────────────────────────────────────────┐
   │ Update server checks version            │
   │ - Compare: 1.0.0 < 1.0.1                │
   │ - Return: updates_available: true       │
   │ - List: [1.0.1 changelog]               │
   └─────────────────┬───────────────────────┘
                     │
                     ▼
5. CUSTOMER DOWNLOADS
   ┌─────────────────────────────────────────┐
   │ Customer clicks "Download 1.0.1"        │
   │ - GET /api/download/1.0.1?key={apiKey}  │
   │ - Verify SHA256 checksum                │
   │ - Extract to temporary directory        │
   └─────────────────┬───────────────────────┘
                     │
                     ▼
6. APPLY UPDATE
   ┌─────────────────────────────────────────┐
   │ Customer clicks "Apply Update"          │
   │ - Backup existing files                 │
   │ - Copy new files from package           │
   │ - Run commands (cache:clear, etc.)      │
   │ - Update local version.json             │
   │ - Verify installation                   │
   └─────────────────────────────────────────┘
```

## Directory Structure

```
/var/www/vhosts/soilsync.shop/
│
├── admin.soilsync.shop/           # Laravel Admin (Gold Master)
│   ├── app/
│   │   └── Http/Controllers/Admin/
│   │       └── SystemUpdateController.php  # Checks for updates
│   ├── resources/views/admin/system/
│   │   └── updates.blade.php      # Update UI
│   ├── version.json               # Current version (source)
│   └── .env
│       └── UPDATE_SERVER_URL      # Points to update server
│       └── UPDATE_SERVER_API_KEY  # Customer-specific key
│
└── update-server/                 # Centralized Update Server
    ├── index.php                  # API endpoints
    ├── config.php                 # API keys, settings
    ├── version.json               # Latest version (copy)
    ├── .htaccess                  # Apache routing
    ├── .gitignore                 # Exclude packages/logs
    │
    ├── packages/                  # Update packages
    │   ├── 1.0.1/
    │   │   ├── manifest.json      # Update metadata
    │   │   └── admin.soilsync.shop/
    │   │       └── app/Services/  # Changed files
    │   ├── 1.0.1.tar.gz          # Compressed package
    │   ├── 1.0.1.tar.gz.sha256   # Checksum
    │   └── 1.0.2/
    │
    ├── logs/                      # Access logs
    │   └── access.log
    │
    ├── setup-update-server.sh     # Apache/SSL setup
    ├── generate-api-key.sh        # Create customer keys
    ├── README.md                  # Full documentation
    ├── QUICKSTART.md              # 5-minute guide
    └── IMPLEMENTATION_SUMMARY.md  # This implementation
```

## API Endpoints

### 1. GET /api/version (Public)

**Purpose**: Get current gold master version  
**Auth**: None (public endpoint)

**Request**:
```bash
curl https://updates.soilsync.shop/api/version
```

**Response**:
```json
{
    "system_version": "1.0.0",
    "release_date": "2026-01-02",
    "release_name": "Gold Master Initial Release",
    "components": {
        "laravel_admin": {
            "version": "1.0.0",
            "files": [...]
        },
        "wordpress_plugins": {
            "mwf-integration": {
                "version": "1.1.0",
                "description": "..."
            }
        }
    }
}
```

### 2. GET /api/updates/{currentVersion} (Authenticated)

**Purpose**: Check for updates available for a specific version  
**Auth**: X-API-Key header or ?key= parameter

**Request**:
```bash
curl -H "X-API-Key: customer-api-key" \
     https://updates.soilsync.shop/api/updates/1.0.0
```

**Response (Updates Available)**:
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

**Response (Up to Date)**:
```json
{
    "updates_available": false,
    "latest_version": "1.0.0",
    "updates": []
}
```

### 3. GET /api/download/{version}?key={apiKey} (Authenticated)

**Purpose**: Download update package  
**Auth**: API key (query parameter)

**Request**:
```bash
curl -O https://updates.soilsync.shop/api/download/1.0.1?key=customer-api-key
```

**Response**: Binary file (application/gzip)
- Filename: `1.0.1.tar.gz`
- Includes SHA256 checksum in response headers

## Security Model

### Authentication
```
┌──────────────────────────────────────────────────────────────┐
│ API Key Generation                                           │
│                                                              │
│ 1. Generate: openssl rand -hex 32                           │
│    Result: 64-character hexadecimal string                   │
│                                                              │
│ 2. Store in config.php:                                      │
│    'api_keys' => [                                           │
│        'a1b2c3...' => 'customer-farm.com',                   │
│        'x9y8z7...' => 'organic-veggies.net'                  │
│    ]                                                         │
│                                                              │
│ 3. Customer adds to .env:                                    │
│    UPDATE_SERVER_API_KEY=a1b2c3...                           │
└──────────────────────────────────────────────────────────────┘
```

### Request Flow
```
Customer Request
      │
      ├─ Sent via HTTPS (encrypted)
      │
      ├─ Contains API key in header or query
      │
      ▼
Update Server
      │
      ├─ Validate API key exists in config.php
      │
      ├─ Check rate limit (100 req/hour per key)
      │
      ├─ Log request (IP, timestamp, endpoint)
      │
      ▼
Response
      │
      ├─ Return requested data OR
      │
      └─ Return 403 Forbidden (invalid key)
```

### Rate Limiting
```
Per API Key Limits:
- 100 requests per hour
- Applies to /api/updates and /api/download
- /api/version is unlimited (public)

Tracking:
- Store last 100 request timestamps per key
- Remove timestamps older than 1 hour
- Deny if count >= 100
```

### Package Integrity
```
Package Creation:
1. Create 1.0.1.tar.gz
2. Generate checksum:
   sha256sum 1.0.1.tar.gz > 1.0.1.tar.gz.sha256

Customer Download:
1. Download 1.0.1.tar.gz
2. Verify checksum matches
3. Extract only if checksum valid
4. Apply update
```

## Scalability Considerations

### Current Setup (Single Server)
```
Gold Master Server (soilsync.shop)
├── Laravel Admin (admin.soilsync.shop)
├── WordPress (soilsync.shop)
├── farmOS (farmos.soilsync.shop)
└── Update Server (updates.soilsync.shop)

Serves: 1-100 customer installations
Bandwidth: ~10GB/month (estimated)
```

### Future Scaling (Multiple VPS)
```
Scenario: "sold a lot of paid hosting"
- 100+ customer VPS installations
- High update download traffic
- Geographic distribution

Solutions:
1. Move update server to dedicated VPS
2. Use CDN (CloudFlare, AWS CloudFront)
3. Multiple update server mirrors
4. Load balancing across mirrors
```

### CDN Integration (Future)
```
┌─────────────────┐
│ Origin Server   │
│ updates.soilsync│
│ .shop           │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ CDN (CloudFlare)│
│ Caches packages │
│ Global edge     │
│ servers         │
└────────┬────────┘
         │
    ┌────┼────┐
    ▼    ▼    ▼
 Cust Cust Cust
   1    2    N

Benefits:
- Reduced origin bandwidth
- Faster downloads globally
- ~$5-10/month for 100+ customers
```

## Monitoring & Analytics

### Key Metrics to Track
```
1. Update Checks
   - How often customers check
   - Which versions are checking
   - Peak check times

2. Downloads
   - Which versions downloaded most
   - Download success rate
   - Average download time

3. Errors
   - Invalid API keys (attempted unauthorized access)
   - Failed downloads
   - Rate limit hits

4. System Health
   - Disk space (packages directory)
   - Bandwidth usage
   - API response times
```

### Log Analysis
```bash
# Update checks by customer
grep "/api/updates" /var/log/apache2/updates-access.log | \
  awk '{print $1}' | sort | uniq -c

# Most popular versions
grep "/api/download" /var/log/apache2/updates-access.log | \
  awk '{print $7}' | cut -d'/' -f4 | sort | uniq -c

# Failed authentications
grep "403" /var/log/apache2/updates-access.log | \
  awk '{print $1, $7}' | sort | uniq -c

# Bandwidth by endpoint
awk '{s[$7]+=$10} END {for(i in s){print i,s[i]}}' \
  /var/log/apache2/updates-access.log
```

## Deployment States

### State 1: Gold Master Only (Current)
```
soilsync.shop
└── Gold master development and testing
    No customers yet
```

### State 2: Update Server Configured (Next)
```
updates.soilsync.shop
└── Centralized update distribution ready
    Waiting for customer deployments
```

### State 3: First Customer (Testing)
```
updates.soilsync.shop → customer1-farm.com
└── Single customer testing update process
    Verify end-to-end workflow
```

### State 4: Production (Goal)
```
updates.soilsync.shop
├── customer1-farm.com
├── customer2-organic.net
├── customer3-csa.io
└── ... (N customers)

All customers can:
- Check for updates
- Download packages
- Apply updates safely
```

## Failure Modes & Recovery

### Update Server Down
```
Symptom: Customer cannot check for updates
Impact: Updates unavailable, existing systems continue working
Recovery: Restart Apache, check DNS, verify SSL

Prevention:
- Uptime monitoring (UptimeRobot, Pingdom)
- Redundant update server (future)
- Automated health checks
```

### Invalid API Key
```
Symptom: 403 Forbidden response
Impact: Single customer cannot update
Recovery: Regenerate API key, update customer .env

Prevention:
- Document API key in customer setup guide
- Automated email with API key on deployment
- Admin UI to regenerate keys
```

### Corrupted Package
```
Symptom: Checksum verification fails
Impact: Customer cannot apply update
Recovery: Recreate package, verify checksum

Prevention:
- Test package download before release
- Automated checksum verification
- Package signing (future)
```

### Customer Applies Bad Update
```
Symptom: Customer site breaks after update
Impact: Single customer site down
Recovery: Restore from backup, rollback version

Prevention:
- Test updates in staging first
- Backup before applying updates
- Rollback mechanism (future)
- Canary deployments (test with 1-2 customers first)
```

## Version History

```
Version 1.0.0 (2026-01-02)
├── Gold Master Initial Release
├── Bulk sync feature
├── Short description sync fix
├── SKU conflict handling
├── User switching plugin
├── Clone preparation scripts
├── Deployment update scripts
└── UPDATE SERVER IMPLEMENTATION

Version 1.0.1 (Future)
└── TBD - First update to test system

Version 1.1.0 (Future)
└── TBD - Feature additions

Version 2.0.0 (Future)
└── TBD - Major architectural changes
```

## Success Metrics

**Update Server is successful when:**

1. **Reliability**: 99.9% uptime
2. **Performance**: < 2 second response times
3. **Security**: Zero unauthorized access attempts succeed
4. **Adoption**: 90%+ customers using update system
5. **Efficiency**: < 5 minutes from release to customer notification

**Current Status**:
- ✅ Code complete
- ⏳ DNS configuration pending
- ⏳ SSL setup pending
- ⏳ First customer deployment pending
- ⏳ Production testing pending

---

**Architecture Designed**: January 2026  
**Purpose**: Scalable multi-VPS update distribution  
**Status**: Ready for deployment
