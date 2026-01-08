#!/bin/bash
#
# Generate API Key for Update Server
# Creates a secure API key and adds it to config.php
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

CONFIG_FILE="/var/www/vhosts/soilsync.shop/update-server/config.php"

# Check arguments
if [ "$#" -lt 1 ]; then
    echo -e "${RED}Error: Customer domain required${NC}"
    echo ""
    echo "Usage: $0 <customer-domain> [--show-only]"
    echo ""
    echo "Examples:"
    echo "  $0 customer-farm.com"
    echo "  $0 organic-veggies.net --show-only"
    echo ""
    exit 1
fi

CUSTOMER_DOMAIN="$1"
SHOW_ONLY="${2:-}"

# Generate secure API key
echo -e "${BLUE}Generating API key for $CUSTOMER_DOMAIN...${NC}"
API_KEY=$(openssl rand -hex 32)

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}API Key Generated${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}Customer Domain:${NC} $CUSTOMER_DOMAIN"
echo -e "${YELLOW}API Key:${NC} $API_KEY"
echo ""

# Show usage
echo -e "${BLUE}Customer Configuration:${NC}"
echo "Add to customer's .env file:"
echo ""
echo -e "${YELLOW}UPDATE_SERVER_URL${NC}=https://updates.soilsync.shop"
echo -e "${YELLOW}UPDATE_SERVER_API_KEY${NC}=$API_KEY"
echo ""

# Add to config if not show-only
if [ "$SHOW_ONLY" != "--show-only" ]; then
    if [ ! -f "$CONFIG_FILE" ]; then
        echo -e "${RED}Error: Config file not found: $CONFIG_FILE${NC}"
        exit 1
    fi
    
    echo -e "${BLUE}Adding to config.php...${NC}"
    
    # Create temporary PHP script to update config
    php << EOF
<?php
\$configFile = '$CONFIG_FILE';
\$config = require \$configFile;

// Add new API key
\$config['api_keys']['$API_KEY'] = '$CUSTOMER_DOMAIN';

// Write back to file
\$export = var_export(\$config, true);
\$content = "<?php\n/**\n * Update Server Configuration\n * \n * Manages API keys, customer domains, and security settings\n */\n\nreturn " . \$export . ";\n";
file_put_contents(\$configFile, \$content);

echo "✓ API key added to config.php\n";
EOF
    
    echo -e "${GREEN}✓ Configuration updated${NC}"
    echo ""
    echo -e "${YELLOW}API key count:${NC} $(php -r "echo count(require('$CONFIG_FILE')['api_keys']);")"
else
    echo -e "${YELLOW}⚠ --show-only flag set: Config not updated${NC}"
    echo ""
    echo -e "${BLUE}Manual Configuration:${NC}"
    echo "Add to $CONFIG_FILE in 'api_keys' array:"
    echo ""
    echo "    '$API_KEY' => '$CUSTOMER_DOMAIN',"
fi

echo ""
echo -e "${GREEN}Done!${NC}"
