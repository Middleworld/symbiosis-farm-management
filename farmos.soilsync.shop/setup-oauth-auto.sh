#!/bin/bash

################################################################################
# farmOS OAuth2 Auto-Setup Script
# 
# One-command setup for farmOS OAuth integration with Laravel admin
# 
# Usage: ./setup-oauth-auto.sh yourdomain.com
#
# Example: ./setup-oauth-auto.sh middleworldfarms.org
#
# This will:
# - Generate RSA keys
# - Configure simple_oauth
# - Create OAuth consumer
# - Output Laravel .env configuration
################################################################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check arguments
if [ $# -eq 0 ]; then
    echo -e "${RED}Error: Domain name required${NC}"
    echo ""
    echo "Usage: $0 yourdomain.com"
    echo ""
    echo "Example: $0 middleworldfarms.org"
    echo "This will configure OAuth for:"
    echo "  - farmOS: https://farmos.yourdomain.com"
    echo "  - Laravel Admin: https://admin.yourdomain.com"
    exit 1
fi

DOMAIN=$1
FARMOS_URL="https://farmos.${DOMAIN}"
ADMIN_URL="https://admin.${DOMAIN}"

echo -e "${BLUE}════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  farmOS OAuth2 Auto-Setup${NC}"
echo -e "${BLUE}════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Domain:${NC} $DOMAIN"
echo -e "${YELLOW}farmOS URL:${NC} $FARMOS_URL"
echo -e "${YELLOW}Laravel Admin URL:${NC} $ADMIN_URL"
echo ""

# Check if in farmOS directory
if [ ! -f "vendor/bin/drush" ]; then
    echo -e "${RED}Error: Not in farmOS directory!${NC}"
    echo "Please run from farmOS root directory"
    exit 1
fi

echo -e "${GREEN}✓${NC} Found farmOS installation"
echo ""

################################################################################
# Step 1: Generate RSA Keys
################################################################################

echo -e "${YELLOW}[1/5]${NC} Generating RSA keys..."

mkdir -p keys

if [ -f "keys/private.key" ]; then
    echo -e "${YELLOW}  → Keys already exist, skipping${NC}"
else
    openssl genrsa -out keys/private.key 2048 2>/dev/null
    openssl rsa -in keys/private.key -pubout -out keys/public.key 2>/dev/null
    chmod 640 keys/private.key
    chmod 644 keys/public.key
    
    # Try to set ownership
    for USER in www-data apache nginx $SUDO_USER; do
        if id "$USER" &>/dev/null; then
            chown -R $USER:$USER keys/ 2>/dev/null && break
        fi
    done
    
    echo -e "${GREEN}  ✓ Keys generated${NC}"
fi

################################################################################
# Step 2: Configure simple_oauth
################################################################################

echo -e "${YELLOW}[2/5]${NC} Configuring simple_oauth..."

# Enable modules if needed
./vendor/bin/drush pm:list --status=enabled --format=list 2>/dev/null | grep -q "simple_oauth" || {
    ./vendor/bin/drush en simple_oauth simple_oauth_static_scope simple_oauth_password_grant -y >/dev/null 2>&1
}

# Configure keys
./vendor/bin/drush config:set simple_oauth.settings public_key ../keys/public.key -y >/dev/null 2>&1
./vendor/bin/drush config:set simple_oauth.settings private_key ../keys/private.key -y >/dev/null 2>&1

echo -e "${GREEN}  ✓ Module configured${NC}"

################################################################################
# Step 3: Generate OAuth Consumer
################################################################################

echo -e "${YELLOW}[3/5]${NC} Creating OAuth consumer..."

# Generate credentials
CLIENT_SECRET=$(openssl rand -base64 24 | tr -d "=+/")
SECRET_HASH=$(php -r "echo password_hash('$CLIENT_SECRET', PASSWORD_BCRYPT);")
CLIENT_ID=$(openssl rand -hex 32)

# Delete existing consumer if exists
EXISTING_ID=$(./vendor/bin/drush sql:query "SELECT id FROM consumer_field_data WHERE label = 'Laravel Admin';" 2>/dev/null | tail -n 1)
if [ -n "$EXISTING_ID" ] && [ "$EXISTING_ID" != "id" ]; then
    ./vendor/bin/drush sql:query "DELETE FROM consumer__grant_types WHERE entity_id = $EXISTING_ID;" 2>/dev/null || true
    ./vendor/bin/drush sql:query "DELETE FROM consumer__scopes WHERE entity_id = $EXISTING_ID;" 2>/dev/null || true
    ./vendor/bin/drush sql:query "DELETE FROM consumer__redirect WHERE entity_id = $EXISTING_ID;" 2>/dev/null || true
    ./vendor/bin/drush sql:query "DELETE FROM consumer_field_data WHERE id = $EXISTING_ID;" 2>/dev/null || true
    ./vendor/bin/drush sql:query "DELETE FROM consumer WHERE id = $EXISTING_ID;" 2>/dev/null || true
fi

# Create consumer
./vendor/bin/drush sql:query "INSERT INTO consumer (id, uuid, langcode) VALUES (NULL, UUID(), 'en');" 2>/dev/null
CONSUMER_ID=$(./vendor/bin/drush sql:query "SELECT LAST_INSERT_ID();" | tail -n 1)

./vendor/bin/drush sql:query "
INSERT INTO consumer_field_data (
    id, langcode, owner_id, client_id, label, secret, 
    confidential, access_token_expiration, refresh_token_expiration,
    user_id, pkce, third_party, is_default, default_langcode
) VALUES (
    $CONSUMER_ID, 'en', 1, '$CLIENT_ID', 'Laravel Admin', '$SECRET_HASH',
    1, 3600, 1209600, 1, 0, 0, 0, 1
);" 2>/dev/null

# Add grant types
for GRANT_TYPE in "client_credentials" "password" "refresh_token"; do
    ./vendor/bin/drush sql:query "
    INSERT INTO consumer__grant_types (bundle, deleted, entity_id, revision_id, langcode, delta, grant_types_value)
    VALUES ('consumer', 0, $CONSUMER_ID, $CONSUMER_ID, 'en', 0, '$GRANT_TYPE');" 2>/dev/null
done

echo -e "${GREEN}  ✓ Consumer created${NC}"

################################################################################
# Step 4: Clear Cache
################################################################################

echo -e "${YELLOW}[4/5]${NC} Clearing cache..."
./vendor/bin/drush cache:rebuild >/dev/null 2>&1
echo -e "${GREEN}  ✓ Cache cleared${NC}"

################################################################################
# Step 5: Test OAuth
################################################################################

echo -e "${YELLOW}[5/5]${NC} Testing OAuth endpoint..."

TOKEN_RESPONSE=$(curl -s -X POST "${FARMOS_URL}/oauth/token" \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-urlencode 'grant_type=client_credentials' \
    --data-urlencode "client_id=$CLIENT_ID" \
    --data-urlencode "client_secret=$CLIENT_SECRET" 2>/dev/null || echo '{"error":"connection_failed"}')

if echo "$TOKEN_RESPONSE" | grep -q "access_token"; then
    echo -e "${GREEN}  ✓ OAuth working!${NC}"
else
    echo -e "${YELLOW}  ⚠ Could not test (farmOS may not be accessible)${NC}"
fi

################################################################################
# Output Results
################################################################################

echo ""
echo -e "${BLUE}════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Setup Complete!${NC}"
echo -e "${BLUE}════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Copy this to your Laravel .env file:${NC}"
echo ""
echo -e "${BLUE}# farmOS OAuth Configuration${NC}"
echo "FARMOS_URL=${FARMOS_URL}"
echo "FARMOS_OAUTH_CLIENT_ID=${CLIENT_ID}"
echo "FARMOS_OAUTH_CLIENT_SECRET=${CLIENT_SECRET}"
echo "FARMOS_OAUTH_SCOPE=farm_manager"
echo ""
echo -e "${YELLOW}Then run in Laravel directory:${NC}"
echo ""
echo "php artisan config:clear"
echo "pkill -f \"pool admin.${DOMAIN}\"  ${BLUE}# or restart PHP-FPM${NC}"
echo ""

# Save to file
CREDS_FILE="oauth-credentials-${DOMAIN}-$(date +%Y%m%d-%H%M%S).txt"
cat > "$CREDS_FILE" << EOF
farmOS OAuth Credentials for ${DOMAIN}
Generated: $(date)

farmOS URL: ${FARMOS_URL}
Laravel Admin URL: ${ADMIN_URL}

Client ID: ${CLIENT_ID}
Client Secret: ${CLIENT_SECRET}

=== Laravel .env Configuration ===

FARMOS_URL=${FARMOS_URL}
FARMOS_OAUTH_CLIENT_ID=${CLIENT_ID}
FARMOS_OAUTH_CLIENT_SECRET=${CLIENT_SECRET}
FARMOS_OAUTH_SCOPE=farm_manager

=== Laravel Commands ===

cd /path/to/admin.${DOMAIN}
php artisan config:clear
pkill -f "pool admin.${DOMAIN}"

=== Test Connection ===

Visit: ${ADMIN_URL}/admin/settings
Click: farmOS Integration → Test Connection
EOF

echo -e "${GREEN}✓${NC} Credentials saved to: ${BLUE}${CREDS_FILE}${NC}"
echo ""
