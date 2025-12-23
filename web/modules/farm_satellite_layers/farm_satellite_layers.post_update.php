<?php

/**
 * @file
 * Post update functions for farmOS Satellite Layers module.
 */

declare(strict_types=1);

use Drupal\Core\Config\FileStorage;

/**
 * Add satellite layers behavior to existing map types.
 */
function farm_satellite_layers_post_update_add_satellite_behavior(&$sandbox) {
  $config_factory = \Drupal::configFactory();
  
  // List of map types to update
  $map_types = [
    'farm_map.map_type.default',
    'farm_map.map_type.dashboard', 
    'farm_map.map_type.asset_list',
    'farm_map.map_type.geofield',
  ];
  
  foreach ($map_types as $map_type_id) {
    $config = $config_factory->getEditable($map_type_id);
    
    if ($config->isNew()) {
      continue; // Skip if config doesn't exist
    }
    
    $behaviors = $config->get('behaviors') ?: [];
    
    // Add satellite_layers behavior if not already present
    if (!in_array('satellite_layers', $behaviors)) {
      $behaviors[] = 'satellite_layers';
      $config->set('behaviors', $behaviors);
      $config->save();
    }
  }
  
  // Clear the map behavior cache
  \Drupal::service('cache.discovery')->deleteAll();
}
