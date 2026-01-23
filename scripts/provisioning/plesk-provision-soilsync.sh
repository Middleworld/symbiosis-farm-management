usr/bin/env bash
# Plesk provisioning for SoilSync customer deployments
# Creates customer + subscriptions (main + admin/farmos/fieldkit) and copies gold-master files.
# Dry-run by default. Use --apply to execute.

set -euo pipefail

usage() {
  echo "Usage: $0 --domain <domain> --customer <customer> --email <email> [--plan <plan>] [--apply]"
  echo "Example: $0 --domain example.com --customer ExampleFarm --email admin@example.com --plan \"Default\" --apply"
  exit 1
}

DOMAIN=""
CUSTOMER=""
EMAIL=""
PLAN="Default"
APPLY="false"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2;;
    --customer) CUSTOMER="$2"; shift 2;;
    --email) EMAIL="$2"; shift 2;;
    --plan) PLAN="$2"; shift 2;;
    --apply) APPLY="true"; shift;;
    *) usage;;
  esac
 done

[[ -z "$DOMAIN" || -z "$CUSTOMER" || -z "$EMAIL" ]] && usage

PLESK_BIN="$(command -v plesk || true)"
if [[ -z "$PLESK_BIN" ]]; then
  PLESK_BIN="/usr/sbin/plesk"
fi

if [[ ! -x "$PLESK_BIN" ]]; then
  echo "Error: Plesk binary not found at $PLESK_BIN"
  exit 1
fi

run() {
  if [[ "$APPLY" == "true" ]]; then
    echo "+ $*"
    eval "$@"
  else
    echo "[dry-run] $*"
  fi
}

sanitize() {
  echo "$1" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]/_/g'
}

truncate() {
  local value="$1";
  local max="$2";
  echo "${value:0:${max}}"
}

MASTER_ROOT="/var/www/vhosts/soilsync.shop"
TARGET_ROOT="/var/www/vhosts"

ADMIN_DOMAIN="admin.${DOMAIN}"
FARMOS_DOMAIN="farmos.${DOMAIN}"
FIELDKIT_DOMAIN="fieldkit.${DOMAIN}"

ADMIN_PATH="${TARGET_ROOT}/${ADMIN_DOMAIN}"
FARMOS_PATH="${TARGET_ROOT}/${FARMOS_DOMAIN}"
FIELDKIT_PATH="${TARGET_ROOT}/${FIELDKIT_DOMAIN}"
MAIN_PATH="${TARGET_ROOT}/${DOMAIN}"

SAFE_DOMAIN="$(sanitize "$DOMAIN")"
DB_HOST="localhost"

WP_DB_NAME="wp_${SAFE_DOMAIN}"
ADMIN_DB_NAME="admin_${SAFE_DOMAIN}"
FARMOS_DB_NAME="farmos_${SAFE_DOMAIN}"

WP_DB_USER="$(truncate "wp_${SAFE_DOMAIN}" 16)"
ADMIN_DB_USER="$(truncate "adm_${SAFE_DOMAIN}" 16)"
FARMOS_DB_USER="$(truncate "far_${SAFE_DOMAIN}" 16)"

WP_DB_PASS="$(openssl rand -base64 18)"
ADMIN_DB_PASS="$(openssl rand -base64 18)"
FARMOS_DB_PASS="$(openssl rand -base64 18)"

WP_DB_PREFIX="wp_${SAFE_DOMAIN}_"

echo "Provisioning domain: $DOMAIN"

echo "== Create customer (if missing) =="
if "$PLESK_BIN" bin customer --info "$CUSTOMER" >/dev/null 2>&1; then
  echo "Customer exists: $CUSTOMER"
else
  CUSTOMER_PASS="$(openssl rand -base64 18)"
  run "$PLESK_BIN bin customer --create '$CUSTOMER' -email '$EMAIL' -passwd '$CUSTOMER_PASS'"
  echo "Customer password (store securely): $CUSTOMER_PASS"
fi

echo "== Create subscriptions =="
run "$PLESK_BIN bin subscription --create '$DOMAIN' -owner '$CUSTOMER' -service-plan '$PLAN'"
run "$PLESK_BIN bin subscription --create '$ADMIN_DOMAIN' -owner '$CUSTOMER' -service-plan '$PLAN'"
run "$PLESK_BIN bin subscription --create '$FARMOS_DOMAIN' -owner '$CUSTOMER' -service-plan '$PLAN'"
run "$PLESK_BIN bin subscription --create '$FIELDKIT_DOMAIN' -owner '$CUSTOMER' -service-plan '$PLAN'"

echo "== Create databases =="
run "$PLESK_BIN bin database --create '$WP_DB_NAME' -type mysql -domain '$DOMAIN'"
run "$PLESK_BIN bin database --create-user '$WP_DB_NAME' -login '$WP_DB_USER' -passwd '$WP_DB_PASS'"

run "$PLESK_BIN bin database --create '$ADMIN_DB_NAME' -type mysql -domain '$ADMIN_DOMAIN'"
run "$PLESK_BIN bin database --create-user '$ADMIN_DB_NAME' -login '$ADMIN_DB_USER' -passwd '$ADMIN_DB_PASS'"

run "$PLESK_BIN bin database --create '$FARMOS_DB_NAME' -type mysql -domain '$FARMOS_DOMAIN'"
run "$PLESK_BIN bin database --create-user '$FARMOS_DB_NAME' -login '$FARMOS_DB_USER' -passwd '$FARMOS_DB_PASS'"

echo "== Copy gold-master files =="
run "rsync -a --delete --exclude '.env' --exclude 'storage/logs' --exclude 'storage/framework/cache/*' '${MASTER_ROOT}/httpdocs/' '${MAIN_PATH}/httpdocs/'"
run "rsync -a --delete --exclude '.env' --exclude 'storage/logs' --exclude 'storage/framework/cache/*' '${MASTER_ROOT}/admin.soilsync.shop/' '${ADMIN_PATH}/httpdocs/'"
run "rsync -a --delete '${MASTER_ROOT}/farmos.soilsync.shop/' '${FARMOS_PATH}/httpdocs/'"
run "rsync -a --delete '${MASTER_ROOT}/fieldkit.soilsync.shop/' '${FIELDKIT_PATH}/httpdocs/'"

echo "== Configure WordPress wp-config.php =="
run "perl -0777 -i -pe 's/define\\(\s*\\'DB_NAME\\'.*?;\s*/define(\\'DB_NAME\\', \\"$WP_DB_NAME\\" );\\n/s' '${MAIN_PATH}/httpdocs/wp-config.php'"
run "perl -0777 -i -pe 's/define\\(\s*\\'DB_USER\\'.*?;\s*/define(\\'DB_USER\\', \\"$WP_DB_USER\\" );\\n/s' '${MAIN_PATH}/httpdocs/wp-config.php'"
run "perl -0777 -i -pe 's/define\\(\s*\\'DB_PASSWORD\\'.*?;\s*/define(\\'DB_PASSWORD\\', \\"$WP_DB_PASS\\" );\\n/s' '${MAIN_PATH}/httpdocs/wp-config.php'"
run "perl -0777 -i -pe 's/define\\(\s*\\'DB_HOST\\'.*?;\s*/define(\\'DB_HOST\\', \\"$DB_HOST\\" );\\n/s' '${MAIN_PATH}/httpdocs/wp-config.php'"
run "perl -0777 -i -pe 's/\$table_prefix\s*=\s*\x27[^\x27]*\x27;/\$table_prefix = \x27${WP_DB_PREFIX}\x27;/s' '${MAIN_PATH}/httpdocs/wp-config.php'"

echo "== Configure Laravel .env =="
if [[ -f "${ADMIN_PATH}/httpdocs/.env.example" && ! -f "${ADMIN_PATH}/httpdocs/.env" ]]; then
  run "cp '${ADMIN_PATH}/httpdocs/.env.example' '${ADMIN_PATH}/httpdocs/.env'"
fi

run "perl -0777 -i -pe 's/^APP_URL=.*/APP_URL=https:\/\/$ADMIN_DOMAIN/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^CUSTOMER_SITE_URL=.*/CUSTOMER_SITE_URL=https:\/\/$DOMAIN/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^WOOCOMMERCE_URL=.*/WOOCOMMERCE_URL=https:\/\/$DOMAIN\//m' '${ADMIN_PATH}/httpdocs/.env'"

run "perl -0777 -i -pe 's/^DB_DATABASE=.*/DB_DATABASE=$ADMIN_DB_NAME/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^DB_USERNAME=.*/DB_USERNAME=$ADMIN_DB_USER/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^DB_PASSWORD=.*/DB_PASSWORD=$ADMIN_DB_PASS/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^DB_HOST=.*/DB_HOST=$DB_HOST/m' '${ADMIN_PATH}/httpdocs/.env'"

run "perl -0777 -i -pe 's/^WP_DB_DATABASE=.*/WP_DB_DATABASE=$WP_DB_NAME/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^WP_DB_USERNAME=.*/WP_DB_USERNAME=$WP_DB_USER/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^WP_DB_PASSWORD=.*/WP_DB_PASSWORD=$WP_DB_PASS/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^WP_DB_HOST=.*/WP_DB_HOST=$DB_HOST/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^WP_DB_PREFIX=.*/WP_DB_PREFIX=${WP_DB_PREFIX}/m' '${ADMIN_PATH}/httpdocs/.env'"

run "perl -0777 -i -pe 's/^FARMOS_URL=.*/FARMOS_URL=https:\/\/$FARMOS_DOMAIN/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^FARMOS_DB_DATABASE=.*/FARMOS_DB_DATABASE=$FARMOS_DB_NAME/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^FARMOS_DB_USERNAME=.*/FARMOS_DB_USERNAME=$FARMOS_DB_USER/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^FARMOS_DB_PASSWORD=.*/FARMOS_DB_PASSWORD=$FARMOS_DB_PASS/m' '${ADMIN_PATH}/httpdocs/.env'"
run "perl -0777 -i -pe 's/^FARMOS_DB_HOST=.*/FARMOS_DB_HOST=$DB_HOST/m' '${ADMIN_PATH}/httpdocs/.env'"

echo "== Configure farmOS database (settings.php) =="
FARMOS_SETTINGS="${FARMOS_PATH}/httpdocs/web/sites/default/settings.php"
if [[ -f "$FARMOS_SETTINGS" ]]; then
  run "perl -0777 -i -pe 's/\x27database\x27\s*=>\s*\x27[^\x27]*\x27/\x27database\x27 => \x27${FARMOS_DB_NAME}\x27/s' '$FARMOS_SETTINGS'"
  run "perl -0777 -i -pe 's/\x27username\x27\s*=>\s*\x27[^\x27]*\x27/\x27username\x27 => \x27${FARMOS_DB_USER}\x27/s' '$FARMOS_SETTINGS'"
  run "perl -0777 -i -pe 's/\x27password\x27\s*=>\s*\x27[^\x27]*\x27/\x27password\x27 => \x27${FARMOS_DB_PASS}\x27/s' '$FARMOS_SETTINGS'"
  run "perl -0777 -i -pe 's/\x27host\x27\s*=>\s*\x27[^\x27]*\x27/\x27host\x27 => \x27${DB_HOST}\x27/s' '$FARMOS_SETTINGS'"
fi

echo "== Save credentials =="
CREDS_FILE="${MAIN_PATH}/provisioning-credentials.txt"
if [[ "$APPLY" == "true" ]]; then
  cat > "$CREDS_FILE" << EOF
Customer: $CUSTOMER
Domain: $DOMAIN

WordPress DB: $WP_DB_NAME
WordPress User: $WP_DB_USER
WordPress Pass: $WP_DB_PASS
WordPress Prefix: $WP_DB_PREFIX

Admin DB: $ADMIN_DB_NAME
Admin User: $ADMIN_DB_USER
Admin Pass: $ADMIN_DB_PASS

farmOS DB: $FARMOS_DB_NAME
farmOS User: $FARMOS_DB_USER
farmOS Pass: $FARMOS_DB_PASS
EOF
  chmod 600 "$CREDS_FILE"
  echo "Credentials written to: $CREDS_FILE"
else
  echo "[dry-run] Credentials file would be written to: $CREDS_FILE"
fi

echo "== Optional: WordPress setup =="
echo "Run after WP install:"
echo "  ${MASTER_ROOT}/scripts/setup-new-wordpress-site.sh ${MAIN_PATH}/httpdocs"

echo "== Next steps =="
cat << EOF
- Verify DB credentials file: ${MAIN_PATH}/provisioning-credentials.txt
- Run Laravel: php artisan config:clear
- Run farmOS cache rebuild as needed
EOF

if [[ "$APPLY" != "true" ]]; then
  echo "Dry-run complete. Re-run with --apply to execute."
fi
