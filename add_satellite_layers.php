
<?php

// Ensure this script is run in a Drupal context (e.g., via Drush php:script)
if (!class_exists('Drupal')) {
    fwrite(STDERR, "\n❌ ERROR: This script must be run using Drush:  ./vendor/bin/drush php:script add_satellite_layers.php\n\n");
    exit(1);
}

/**
 * Script to add satellite_layers behavior to map types
 * Run this after cache rebuilds to restore satellite layers
 */


// Map types and their required behaviors
$map_configurations = [
    'farm_map.map_type.dashboard' => ['locations', 'satellite_layers'],
    'farm_map.map_type.asset_list' => ['satellite_layers'],
    'farm_map.map_type.geofield' => ['wkt', 'satellite_layers'],
    'farm_map.map_type.locations' => ['satellite_layers'],
    'farm_map.map_type.default' => ['satellite_layers'],
];

echo "🛰️  farmOS Satellite Layers Configurator\n";
echo "=========================================\n\n";

$updated_count = 0;


$not_found = [];
foreach ($map_configurations as $map_type_id => $required_behaviors) {
    // Get current configuration
    $config = \Drupal::configFactory()->getEditable($map_type_id);

    if ($config->isNew()) {
        echo "⚠️  Config $map_type_id does not exist, skipping...\n";
        $not_found[] = $map_type_id;
        continue;
    }

    $current_behaviors = $config->get('behaviors') ?: [];
    $needs_update = false;
    $missing_behaviors = [];

    // Check what behaviors are missing
    foreach ($required_behaviors as $behavior) {
        if (!in_array($behavior, $current_behaviors)) {
            $missing_behaviors[] = $behavior;
            $needs_update = true;
        }
    }

    if ($needs_update) {
        // Merge behaviors (avoid duplicates)
        $new_behaviors = array_unique(array_merge($current_behaviors, $required_behaviors));
        $config->set('behaviors', $new_behaviors);
        $config->save();
        echo "✅ Updated $map_type_id (added: " . implode(', ', $missing_behaviors) . ")\n";
        $updated_count++;
    } else {
        echo "ℹ️  $map_type_id already has required behaviors\n";
    }
}


echo "\n🎯 Configuration complete! Updated $updated_count map types.\n";
if (count($not_found) > 0) {
    echo "\n⚠️  The following configs were not found: \n   - " . implode("\n   - ", $not_found) . "\n";
}

if ($updated_count > 0) {
    echo "\n💡 TIP: If satellite layers disappear after cache rebuilds, re-run this script:\n";
    echo "   cd /var/www/vhosts/middleworldfarms.org/subdomains/farmos\n";
    echo "   ./vendor/bin/drush php:script add_satellite_layers.php\n";
}

?>
