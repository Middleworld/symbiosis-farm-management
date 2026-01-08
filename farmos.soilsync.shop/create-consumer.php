<?php
// Generate credentials
$client_id = 'laravel_sso_' . bin2hex(random_bytes(8));
$client_secret = bin2hex(random_bytes(32));

// Hash the secret
$hashed_secret = password_hash($client_secret, PASSWORD_BCRYPT);

echo "Generated OAuth Credentials:\n";
echo "Client ID: $client_id\n";
echo "Client Secret: $client_secret\n";
echo "Hashed Secret: $hashed_secret\n";

// Save to file for Laravel
file_put_contents('/tmp/oauth_credentials.txt', "FARMOS_OAUTH_CLIENT_ID=$client_id\nFARMOS_OAUTH_CLIENT_SECRET=$client_secret\n");
echo "\n✅ Credentials saved to /tmp/oauth_credentials.txt\n";
