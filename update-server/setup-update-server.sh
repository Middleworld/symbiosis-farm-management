#!/bin/bash
#
# Setup Script for Centralized Update Server
# Configures subdomain, SSL, and permissions for updates.soilsync.shop
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="updates.soilsync.shop"
PORT="${1:-443}"  # Default to 443 (standard HTTPS), or custom port from argument
UPDATE_SERVER_DIR="/var/www/vhosts/soilsync.shop/update-server"
APACHE_SITES_DIR="/etc/apache2/sites-available"
APACHE_PORTS_FILE="/etc/apache2/ports.conf"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Middle World Farms Update Server Setup${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}Error: This script must be run as root${NC}"
   echo "Usage: sudo ./setup-update-server.sh [port]"
   exit 1
fi

# Display configuration
echo -e "${YELLOW}Configuration:${NC}"
echo "  Domain: $DOMAIN"
echo "  Port: $PORT"
echo "  Directory: $UPDATE_SERVER_DIR"
echo ""

# Step 1: Create directory structure
echo -e "${BLUE}[1/8] Creating directory structure...${NC}"
mkdir -p "$UPDATE_SERVER_DIR/packages"
mkdir -p "$UPDATE_SERVER_DIR/logs"
chmod 750 "$UPDATE_SERVER_DIR/packages"
chmod 750 "$UPDATE_SERVER_DIR/logs"
echo -e "${GREEN}✓ Directories created${NC}"

# Step 2: Set permissions
echo -e "${BLUE}[2/8] Setting permissions...${NC}"
chown -R www-data:www-data "$UPDATE_SERVER_DIR"
chmod -R 755 "$UPDATE_SERVER_DIR"
chmod 640 "$UPDATE_SERVER_DIR/config.php"
echo -e "${GREEN}✓ Permissions set${NC}"

# Step 3: Configure Apache port (if custom)
if [ "$PORT" != "443" ] && [ "$PORT" != "80" ]; then
    echo -e "${BLUE}[3/8] Configuring custom port $PORT...${NC}"
    if ! grep -q "Listen $PORT" "$APACHE_PORTS_FILE"; then
        echo "Listen $PORT" >> "$APACHE_PORTS_FILE"
        echo -e "${GREEN}✓ Added Listen $PORT to ports.conf${NC}"
    else
        echo -e "${YELLOW}⚠ Port $PORT already configured${NC}"
    fi
else
    echo -e "${BLUE}[3/8] Using standard port $PORT${NC}"
fi

# Step 4: Create Apache virtual host config
echo -e "${BLUE}[4/8] Creating Apache virtual host...${NC}"
VHOST_FILE="$APACHE_SITES_DIR/$DOMAIN-$PORT.conf"

cat > "$VHOST_FILE" << EOF
<VirtualHost *:$PORT>
    ServerName $DOMAIN
    DocumentRoot $UPDATE_SERVER_DIR

    <Directory $UPDATE_SERVER_DIR>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # PHP settings
        <IfModule mod_php.c>
            php_flag display_errors off
            php_flag log_errors on
        </IfModule>
    </Directory>

    # Deny direct access to sensitive directories
    <DirectoryMatch "^$UPDATE_SERVER_DIR/(logs|config|\.git)">
        Require all denied
    </DirectoryMatch>

EOF

# Add SSL if using HTTPS port
if [ "$PORT" = "443" ]; then
    cat >> "$VHOST_FILE" << EOF
    # SSL Configuration
    SSLEngine on
    # Note: SSL certificate paths need to be configured after obtaining cert
    # SSLCertificateFile /etc/letsencrypt/live/$DOMAIN/fullchain.pem
    # SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN/privkey.pem
    
    # SSL Security Settings
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on

EOF
fi

cat >> "$VHOST_FILE" << EOF
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/$DOMAIN-error.log
    CustomLog \${APACHE_LOG_DIR}/$DOMAIN-access.log combined
    
    # Additional security headers
    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "DENY"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
</VirtualHost>
EOF

echo -e "${GREEN}✓ Virtual host config created: $VHOST_FILE${NC}"

# Step 5: Enable Apache modules
echo -e "${BLUE}[5/8] Enabling required Apache modules...${NC}"
a2enmod rewrite headers ssl 2>/dev/null || true
echo -e "${GREEN}✓ Modules enabled${NC}"

# Step 6: Enable site
echo -e "${BLUE}[6/8] Enabling site...${NC}"
a2ensite "$DOMAIN-$PORT.conf"
echo -e "${GREEN}✓ Site enabled${NC}"

# Step 7: Test Apache configuration
echo -e "${BLUE}[7/8] Testing Apache configuration...${NC}"
if apache2ctl configtest; then
    echo -e "${GREEN}✓ Apache configuration is valid${NC}"
else
    echo -e "${RED}✗ Apache configuration has errors${NC}"
    exit 1
fi

# Step 8: Reload Apache
echo -e "${BLUE}[8/8] Reloading Apache...${NC}"
systemctl reload apache2
echo -e "${GREEN}✓ Apache reloaded${NC}"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo ""

# DNS check
echo -e "${BLUE}1. Configure DNS:${NC}"
echo "   Add A record: $DOMAIN → $(hostname -I | awk '{print $1}')"
echo ""

# SSL certificate
if [ "$PORT" = "443" ]; then
    echo -e "${BLUE}2. Obtain SSL Certificate:${NC}"
    echo "   sudo certbot --apache -d $DOMAIN"
    echo ""
    echo -e "${YELLOW}   Note: After obtaining certificate, update:${NC}"
    echo "   $VHOST_FILE"
    echo "   Uncomment SSLCertificateFile and SSLCertificateKeyFile lines"
    echo ""
fi

# Firewall
echo -e "${BLUE}3. Configure Firewall:${NC}"
echo "   sudo ufw allow $PORT/tcp"
echo "   sudo ufw reload"
echo ""

# API keys
echo -e "${BLUE}4. Configure API Keys:${NC}"
echo "   Edit: $UPDATE_SERVER_DIR/config.php"
echo "   Add customer API keys in 'api_keys' array"
echo ""

# Test
echo -e "${BLUE}5. Test the Update Server:${NC}"
echo "   curl http://$DOMAIN:$PORT/api/version"
if [ "$PORT" = "443" ]; then
    echo "   (After SSL setup: https://$DOMAIN/api/version)"
fi
echo ""

# Monitor
echo -e "${BLUE}6. Monitor Logs:${NC}"
echo "   tail -f /var/log/apache2/$DOMAIN-access.log"
echo "   tail -f /var/log/apache2/$DOMAIN-error.log"
echo ""

echo -e "${GREEN}Update server is ready for configuration!${NC}"
