#!/bin/bash

################################################################################
# farmOS OAuth2 Setup Script
# 
# Automates the complete OAuth2 setup for farmOS integration with Laravel admin
# Run this script from the farmOS root directory
#
# Usage: ./setup-oauth.sh [options]
# Options:
#   --consumer-label "Name"   Label for OAuth consumer (default: Laravel Admin)
#   --user-id N               User ID to assign consumer to (default: 1)
#   --skip-keys              Skip RSA key generation (if keys already exist)
#   --help                   Show this help message
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
CONSUMER_LABEL="Laravel Admin"
USER_ID=1
SKIP_KEYS=false
FARMOS_DIR=$(pwd)

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --consumer-label)
            CONSUMER_LABEL="$2"
            shift 2
            ;;
        --user-id)
            USER_ID="$2"
            shift 2
            ;;
        --skip-keys)
            SKIP_KEYS=true
            shift
            ;;
        --help)
            head -n 15 "$0" | tail -n 13
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   farmOS OAuth2 Setup Script${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Check if we're in farmOS directory
if [ ! -f "vendor/bin/drush" ]; then
    echo -e "${RED}Error: vendor/bin/drush not found!${NC}"
    echo "Please run this script from the farmOS root directory."
    exit 1
fi

echo -e "${GREEN}✓${NC} Found farmOS installation"
echo ""

################################################################################
# Step 1: Generate RSA Keys
################################################################################

if [ "$SKIP_KEYS" = false ]; then
    echo -e "${YELLOW}Step 1/5:${NC} Generating RSA key pair for OAuth..."
    
    # Create keys directory if it doesn't exist
    mkdir -p keys
    
    if [ -f "keys/private.key" ]; then
        echo -e "${YELLOW}⚠ Warning:${NC} keys/private.key already exists"
        read -p "Overwrite existing keys? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "Skipping key generation"
            SKIP_KEYS=true
        fi
    fi
    
    if [ "$SKIP_KEYS" = false ]; then
        # Generate private key
        openssl genrsa -out keys/private.key 2048 2>/dev/null
        
        # Extract public key
        openssl rsa -in keys/private.key -pubout -out keys/public.key 2>/dev/null
        
        # Set permissions
        chmod 640 keys/private.key
        chmod 644 keys/public.key
        
        # Get web server user (try common ones)
        WEB_USER="www-data"
        if id "apache" &>/dev/null; then
            WEB_USER="apache"
        elif id "nginx" &>/dev/null; then
            WEB_USER="nginx"
        elif [ -n "$SUDO_USER" ]; then
            WEB_USER="$SUDO_USER"
        fi
        
        chown -R $WEB_USER:$WEB_USER keys/ 2>/dev/null || {
            echo -e "${YELLOW}⚠ Warning:${NC} Could not set ownership to $WEB_USER"
            echo "Please manually run: chown -R <web-user>:<web-group> keys/"
        }
        
        echo -e "${GREEN}✓${NC} RSA keys generated successfully"
        echo "  - Private key: keys/private.key"
        echo "  - Public key: keys/public.key"
    fi
else
    echo -e "${YELLOW}Step 1/5:${NC} Skipping RSA key generation (--skip-keys)"
fi

echo ""

################################################################################
# Step 2: Configure simple_oauth Module
################################################################################

echo -e "${YELLOW}Step 2/5:${NC} Configuring simple_oauth module..."

# Check if simple_oauth is enabled
if ! ./vendor/bin/drush pm:list --status=enabled --format=list | grep -q "simple_oauth"; then
    echo "Enabling simple_oauth modules..."
    ./vendor/bin/drush en simple_oauth simple_oauth_static_scope simple_oauth_password_grant -y
fi

# Configure key paths
./vendor/bin/drush config:set simple_oauth.settings public_key ../keys/public.key -y > /dev/null 2>&1
./vendor/bin/drush config:set simple_oauth.settings private_key ../keys/private.key -y > /dev/null 2>&1

echo -e "${GREEN}✓${NC} simple_oauth configured"
echo ""

################################################################################
# Step 3: Generate OAuth Consumer Credentials
################################################################################

echo -e "${YELLOW}Step 3/5:${NC} Creating OAuth consumer..."

# Generate client secret (strong random password)
CLIENT_SECRET=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-32)

# Hash the secret for database storage
SECRET_HASH=$(php -r "echo password_hash('$CLIENT_SECRET', PASSWORD_BCRYPT);")

# Generate a pseudo-random client ID (similar to Drupal's format)
CLIENT_ID=$(openssl rand -hex 32)

# Check if consumer already exists
EXISTING_CONSUMER=$(./vendor/bin/drush sql:query "SELECT client_id FROM consumer_field_data WHERE label = '$CONSUMER_LABEL';" 2>/dev/null | tail -n 1)

if [ -n "$EXISTING_CONSUMER" ] && [ "$EXISTING_CONSUMER" != "client_id" ]; then
    echo -e "${YELLOW}⚠ Warning:${NC} Consumer '$CONSUMER_LABEL' already exists"
    read -p "Delete and recreate? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Get the consumer ID
        CONSUMER_ID=$(./vendor/bin/drush sql:query "SELECT id FROM consumer_field_data WHERE label = '$CONSUMER_LABEL';" 2>/dev/null | tail -n 1)
        
        # Delete related records first
        ./vendor/bin/drush sql:query "DELETE FROM consumer__grant_types WHERE entity_id = $CONSUMER_ID;" 2>/dev/null || true
        ./vendor/bin/drush sql:query "DELETE FROM consumer__scopes WHERE entity_id = $CONSUMER_ID;" 2>/dev/null || true
        ./vendor/bin/drush sql:query "DELETE FROM consumer__redirect WHERE entity_id = $CONSUMER_ID;" 2>/dev/null || true
        ./vendor/bin/drush sql:query "DELETE FROM consumer_field_data WHERE id = $CONSUMER_ID;" 2>/dev/null || true
        ./vendor/bin/drush sql:query "DELETE FROM consumer WHERE id = $CONSUMER_ID;" 2>/dev/null || true
    else
        echo "Keeping existing consumer. Exiting."
        exit 0
    fi
fi

# Insert consumer into database
./vendor/bin/drush sql:query "
INSERT INTO consumer (id, uuid, langcode) 
VALUES (NULL, UUID(), 'en');
" 2>/dev/null

# Get the inserted consumer ID
CONSUMER_DB_ID=$(./vendor/bin/drush sql:query "SELECT MAX(id) FROM consumer;" | tail -n 1)

# Insert consumer field data
./vendor/bin/drush sql:query "
INSERT INTO consumer_field_data (
    id, langcode, owner_id, client_id, label, secret, 
    confidential, access_token_expiration, refresh_token_expiration,
    user_id, pkce, third_party, is_default, default_langcode
) VALUES (
    $CONSUMER_DB_ID, 'en', $USER_ID, '$CLIENT_ID', '$CONSUMER_LABEL', '$SECRET_HASH',
    1, 3600, 1209600, $USER_ID, 0, 0, 0, 1
);
" 2>/dev/null

# Add grant types
DELTA=0
for GRANT_TYPE in "client_credentials" "password" "refresh_token"; do
    ./vendor/bin/drush sql:query "
    INSERT INTO consumer__grant_types (bundle, deleted, entity_id, revision_id, langcode, delta, grant_types_value)
    VALUES ('consumer', 0, $CONSUMER_DB_ID, $CONSUMER_DB_ID, 'en', $DELTA, '$GRANT_TYPE');
    " 2>/dev/null
    DELTA=$((DELTA + 1))
done

echo -e "${GREEN}✓${NC} OAuth consumer created"
echo ""

################################################################################
# Step 4: Clear Caches
################################################################################

echo -e "${YELLOW}Step 4/5:${NC} Clearing farmOS cache..."
./vendor/bin/drush cache:rebuild > /dev/null 2>&1
echo -e "${GREEN}✓${NC} Cache cleared"
echo ""

################################################################################
# Step 5: Test OAuth Token
################################################################################

echo -e "${YELLOW}Step 5/5:${NC} Testing OAuth token endpoint..."

# Get farmOS URL from settings.php or environment
FARMOS_URL=$(./vendor/bin/drush config:get system.site page.front --format=string 2>/dev/null | sed 's|/home||' || echo "https://$(hostname)")

# Test token request
TOKEN_RESPONSE=$(curl -s -X POST "${FARMOS_URL}/oauth/token" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode 'grant_type=client_credentials' \
    --data-urlencode "client_id=$CLIENT_ID" \
    --data-urlencode "client_secret=$CLIENT_SECRET" || echo '{"error":"curl_failed"}')

if echo "$TOKEN_RESPONSE" | grep -q "access_token"; then
    echo -e "${GREEN}✓${NC} OAuth token test successful!"
else
    echo -e "${RED}✗${NC} OAuth token test failed"
    echo "Response: $TOKEN_RESPONSE"
    echo ""
    echo "This might be normal if farmOS is not accessible via URL: $FARMOS_URL"
    echo "You can test manually after setting up Laravel."
fi

echo ""

################################################################################
# Output Configuration
################################################################################

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   Setup Complete!${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}OAuth Consumer Credentials:${NC}"
echo ""
echo -e "  ${BLUE}Client ID:${NC}"
echo "  $CLIENT_ID"
echo ""
echo -e "  ${BLUE}Client Secret:${NC}"
echo "  $CLIENT_SECRET"
echo ""
echo -e "${RED}⚠ IMPORTANT: Save these credentials securely!${NC}"
echo "You will need them to configure the Laravel admin application."
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo ""
echo "1. Update Laravel .env file:"
echo "   ${BLUE}FARMOS_URL=${NC}$FARMOS_URL"
echo "   ${BLUE}FARMOS_OAUTH_CLIENT_ID=${NC}$CLIENT_ID"
echo "   ${BLUE}FARMOS_OAUTH_CLIENT_SECRET=${NC}$CLIENT_SECRET"
echo "   ${BLUE}FARMOS_OAUTH_SCOPE=${NC}farm_manager"
echo ""
echo "2. Clear Laravel config cache:"
echo "   ${BLUE}cd /path/to/laravel${NC}"
echo "   ${BLUE}php artisan config:clear${NC}"
echo "   ${BLUE}pkill -f \"pool your-site\"${NC}  # or restart PHP-FPM"
echo ""
echo "3. Test connection in Laravel admin:"
echo "   Visit: ${BLUE}/admin/settings${NC} → farmOS Integration → Test Connection"
echo ""

# Save credentials to file
CREDENTIALS_FILE="$FARMOS_DIR/oauth-credentials-$(date +%Y%m%d-%H%M%S).txt"
cat > "$CREDENTIALS_FILE" << EOF
farmOS OAuth Credentials
Generated: $(date)

Client ID: $CLIENT_ID
Client Secret: $CLIENT_SECRET
Consumer Label: $CONSUMER_LABEL
User ID: $USER_ID
Grant Types: client_credentials, password, refresh_token
Scope: farm_manager
Token Expiration: 3600s (1 hour)

Laravel .env Configuration:
FARMOS_URL=$FARMOS_URL
FARMOS_OAUTH_CLIENT_ID=$CLIENT_ID
FARMOS_OAUTH_CLIENT_SECRET=$CLIENT_SECRET
FARMOS_OAUTH_SCOPE=farm_manager

Commands to run in Laravel:
cd /path/to/laravel
php artisan config:clear
pkill -f "pool your-site"
EOF

echo -e "${GREEN}✓${NC} Credentials saved to: ${BLUE}$CREDENTIALS_FILE${NC}"
echo ""
echo -e "${YELLOW}Documentation:${NC}"
echo "  - Quick setup: docs/FARMOS_OAUTH_QUICKSTART.md"
echo "  - Full guide: docs/FARMOS_OAUTH_SETUP_COMPLETE.md"
echo ""
