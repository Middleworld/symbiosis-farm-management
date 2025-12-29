<?php

use Drupal\Core\Entity\Entity\EntityFormDisplay;

// Load the form display
$form_display = EntityFormDisplay::load('taxonomy_term.plant_type.default');

if ($form_display) {
  echo "Reordering fields on plant_type edit form...\n\n";
  
  // Define the field order with weights
  $field_order = [
    // Basic Info (top)
    'name' => -5,
    'description' => 0,
    'parent' => 1,
    
    // Growing Details
    'season_type' => 10,
    'frost_tolerance' => 11,
    'planting_method' => 12,
    
    // Germination
    'germination_days_min' => 20,
    'germination_days_max' => 21,
    'germination_temp_optimal' => 22,
    'planting_depth_inches' => 23,
    
    // Timing
    'maturity_days' => 30,
    'transplant_days' => 31,
    'harvest_days' => 32,
    
    // Spacing
    'in_row_spacing_cm' => 40,
    'between_row_spacing_cm' => 41,
    
    // Other
    'harvest_method' => 50,
    'crop_family' => 51,
    'companions' => 52,
    
    // Media/Files
    'image' => 80,
    'file' => 81,
    'external_uri' => 82,
    
    // System
    'langcode' => 90,
    'status' => 100,
  ];
  
  foreach ($field_order as $field_name => $weight) {
    $component = $form_display->getComponent($field_name);
    if ($component) {
      $component['weight'] = $weight;
      $form_display->setComponent($field_name, $component);
      echo "Set weight $weight for: $field_name\n";
    }
  }
  
  $form_display->save();
  echo "\n✅ Field order updated!\n";
  echo "\nClearing cache...\n";
  drupal_flush_all_caches();
  echo "Done!\n";
} else {
  echo "❌ Could not load form display\n";
}
