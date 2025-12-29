<?php
// Create a known hash for testing
$secret = 'test123';
$hash = password_hash($secret, PASSWORD_DEFAULT);
echo "Secret: $secret\n";
echo "Hash: $hash\n";
echo "Verification: " . (password_verify($secret, $hash) ? 'PASS' : 'FAIL') . "\n";
?>
