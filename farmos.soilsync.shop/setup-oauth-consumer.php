<?php
/**
 * Script to create/update OAuth consumer in farmOS
 * Run: cd /var/www/vhosts/soilsync.shop/farmos.soilsync.shop && php setup-oauth-consumer.php
 */

// Bootstrap Drupal
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'web/autoload.php';
$kernel = new DrupalKernel('prod', $autoloader);
$request = Request::createFromGlobals();
$kernel->boot();
$kernel->prepareLegacyRequest($request);

// Generate new OAuth credentials
$client_id = 'laravel_admin_' . bin2hex(random_bytes(8));
$client_secret = bin2hex(random_bytes(32));

echo "=== farmOS OAuth Consumer Setup ===\n\n";
echo "Client ID: $client_id\n";
echo "Client Secret: $client_secret\n\n";

// Create consumer entity
$consumer_storage = \Drupal::entityTypeManager()->getStorage('consumer');

// Check if Laravel consumer already exists
$existing = $consumer_storage->loadByProperties(['label' => 'Laravel Admin SSO']);
if (!empty($existing)) {
    $consumer = reset($existing);
    echo "Found existing consumer (ID: {$consumer->id()}), updating...\n";
} else {
    $consumer = $consumer_storage->create([
        'label' => 'Laravel Admin SSO',
        'description' => 'OAuth consumer for Laravel admin SSO and FieldKit authentication',
        'is_default' => false,
    ]);
    echo "Creating new consumer...\n";
}

// Set consumer properties
$consumer->set('client_id', $client_id);
$consumer->set('secret', $client_secret); // Drupal will hash this automatically
$consumer->set('confidential', true);

// Set grant types
$consumer->set('grant_types', [
    ['value' => 'client_credentials'],
    ['value' => 'password'],
    ['value' => 'refresh_token'],
]);

// Set scopes (optional - leave empty to allow all scopes)
$consumer->set('scopes', []);

// Save consumer
$consumer->save();

echo "✅ Consumer saved successfully!\n\n";
echo "=== Update Laravel .env with these values ===\n";
echo "FARMOS_OAUTH_CLIENT_ID=$client_id\n";
echo "FARMOS_OAUTH_CLIENT_SECRET=$client_secret\n\n";

echo "=== Next Steps ===\n";
echo "1. Update /var/www/vhosts/soilsync.shop/admin.soilsync.shop/.env with the values above\n";
echo "2. Run: cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop && php artisan config:clear\n";
echo "3. Logout and login again at https://admin.soilsync.shop/sso/login\n";
echo "4. Visit https://fieldkit.soilsync.shop\n";
