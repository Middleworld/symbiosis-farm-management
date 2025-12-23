<?php

/**
 * Set default spacing and planting method values based on crop families and crop types
 */

use Drupal\taxonomy\Entity\Term;

// Define spacing standards (in cm) and planting methods by crop family
$family_defaults = [
  // Brassicaceae - varies by crop type
  'Brassicaceae' => [
    'default' => [
      'in_row' => 30,
      'between_row' => 45,
      'method' => 'transplant'
    ],
    'patterns' => [
      'Cabbage' => ['in_row' => 45, 'between_row' => 60, 'method' => 'transplant'],
      'Cauliflower' => ['in_row' => 50, 'between_row' => 60, 'method' => 'transplant'],
      'Broccoli' => ['in_row' => 45, 'between_row' => 60, 'method' => 'transplant'],
      'Brussels Sprout' => ['in_row' => 60, 'between_row' => 75, 'method' => 'transplant'],
      'Kale' => ['in_row' => 45, 'between_row' => 60, 'method' => 'transplant'],
      'Rocket' => ['in_row' => 15, 'between_row' => 20, 'method' => 'direct'],
      'Radish' => ['in_row' => 5, 'between_row' => 15, 'method' => 'direct'],
      'Turnip' => ['in_row' => 10, 'between_row' => 30, 'method' => 'direct'],
      'Wallflower' => ['in_row' => 25, 'between_row' => 30, 'method' => 'transplant'],
      'Stock' => ['in_row' => 25, 'between_row' => 30, 'method' => 'transplant'],
    ]
  ],
  
  // Asteraceae - mostly flowers, some lettuce
  'Asteraceae' => [
    'default' => [
      'in_row' => 30,
      'between_row' => 30,
      'method' => 'either'
    ],
    'patterns' => [
      'Lettuce' => ['in_row' => 25, 'between_row' => 30, 'method' => 'either'],
      'Endive' => ['in_row' => 25, 'between_row' => 30, 'method' => 'either'],
      'Chicory' => ['in_row' => 25, 'between_row' => 30, 'method' => 'either'],
      'Sunflower' => ['in_row' => 45, 'between_row' => 60, 'method' => 'direct'],
      'Zinnia' => ['in_row' => 30, 'between_row' => 30, 'method' => 'either'],
      'Cosmos' => ['in_row' => 30, 'between_row' => 30, 'method' => 'direct'],
      'Dahlia' => ['in_row' => 60, 'between_row' => 60, 'method' => 'transplant'],
    ]
  ],
  
  // Apiaceae - carrots, parsnips, herbs
  'Apiaceae' => [
    'default' => [
      'in_row' => 5,
      'between_row' => 30,
      'method' => 'direct'
    ],
    'patterns' => [
      'Carrot' => ['in_row' => 5, 'between_row' => 30, 'method' => 'direct'],
      'Parsnip' => ['in_row' => 10, 'between_row' => 30, 'method' => 'direct'],
      'Parsley' => ['in_row' => 15, 'between_row' => 25, 'method' => 'either'],
      'Coriander' => ['in_row' => 10, 'between_row' => 20, 'method' => 'direct'],
      'Dill' => ['in_row' => 15, 'between_row' => 25, 'method' => 'direct'],
      'Fennel' => ['in_row' => 30, 'between_row' => 40, 'method' => 'either'],
    ]
  ],
  
  // Lamiaceae - herbs
  'Lamiaceae' => [
    'default' => [
      'in_row' => 25,
      'between_row' => 30,
      'method' => 'either'
    ],
    'patterns' => [
      'Basil' => ['in_row' => 20, 'between_row' => 25, 'method' => 'transplant'],
      'Mint' => ['in_row' => 30, 'between_row' => 45, 'method' => 'transplant'],
      'Oregano' => ['in_row' => 25, 'between_row' => 30, 'method' => 'transplant'],
      'Thyme' => ['in_row' => 20, 'between_row' => 25, 'method' => 'transplant'],
      'Sage' => ['in_row' => 45, 'between_row' => 60, 'method' => 'transplant'],
    ]
  ],
  
  // Allium - onions, leeks, garlic
  'Allium' => [
    'default' => [
      'in_row' => 10,
      'between_row' => 30,
      'method' => 'either'
    ],
    'patterns' => [
      'Onion' => ['in_row' => 10, 'between_row' => 30, 'method' => 'either'],
      'Leek' => ['in_row' => 15, 'between_row' => 30, 'method' => 'transplant'],
      'Garlic' => ['in_row' => 15, 'between_row' => 30, 'method' => 'direct'],
      'Chive' => ['in_row' => 20, 'between_row' => 25, 'method' => 'transplant'],
    ]
  ],
  
  // Solanaceae - tomatoes, peppers, potatoes
  'Solanaceae' => [
    'default' => [
      'in_row' => 45,
      'between_row' => 60,
      'method' => 'transplant'
    ],
    'patterns' => [
      'Tomato' => ['in_row' => 45, 'between_row' => 60, 'method' => 'transplant'],
      'Pepper' => ['in_row' => 45, 'between_row' => 60, 'method' => 'transplant'],
      'Aubergine' => ['in_row' => 60, 'between_row' => 75, 'method' => 'transplant'],
      'Petunia' => ['in_row' => 25, 'between_row' => 30, 'method' => 'transplant'],
      'Nicotiana' => ['in_row' => 30, 'between_row' => 30, 'method' => 'transplant'],
    ]
  ],
  
  // Cucurbitaceae - cucumbers, squash, pumpkins
  'Cucurbitaceae' => [
    'default' => [
      'in_row' => 60,
      'between_row' => 90,
      'method' => 'either'
    ],
    'patterns' => [
      'Cucumber' => ['in_row' => 45, 'between_row' => 75, 'method' => 'either'],
      'Courgette' => ['in_row' => 90, 'between_row' => 90, 'method' => 'either'],
      'Squash' => ['in_row' => 90, 'between_row' => 120, 'method' => 'either'],
      'Pumpkin' => ['in_row' => 120, 'between_row' => 180, 'method' => 'either'],
      'Melon' => ['in_row' => 60, 'between_row' => 90, 'method' => 'transplant'],
    ]
  ],
  
  // Leguminosae - peas, beans
  'Leguminosae' => [
    'default' => [
      'in_row' => 10,
      'between_row' => 45,
      'method' => 'direct'
    ],
    'patterns' => [
      'Pea' => ['in_row' => 5, 'between_row' => 45, 'method' => 'direct'],
      'Bean' => ['in_row' => 10, 'between_row' => 45, 'method' => 'direct'],
      'Broad Bean' => ['in_row' => 20, 'between_row' => 60, 'method' => 'direct'],
      'Runner Bean' => ['in_row' => 15, 'between_row' => 60, 'method' => 'direct'],
      'Sweet Pea' => ['in_row' => 15, 'between_row' => 30, 'method' => 'either'],
    ]
  ],
  
  // Amaranthaceae - beets, chard, spinach
  'Amaranthaceae' => [
    'default' => [
      'in_row' => 10,
      'between_row' => 30,
      'method' => 'direct'
    ],
    'patterns' => [
      'Beetroot' => ['in_row' => 10, 'between_row' => 30, 'method' => 'direct'],
      'Chard' => ['in_row' => 15, 'between_row' => 30, 'method' => 'either'],
      'Spinach' => ['in_row' => 10, 'between_row' => 25, 'method' => 'direct'],
    ]
  ],
  
  // Default for unknown families
  'default' => [
    'in_row' => 30,
    'between_row' => 30,
    'method' => 'either'
  ]
];

// Load all plant_type terms
$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$terms = $storage->loadByProperties(['vid' => 'plant_type']);

$updated = 0;
$skipped = 0;
$by_family = [];

echo "Setting default spacing values for plant_type taxonomy...\n\n";

foreach ($terms as $term) {
  $term_name = $term->label();
  
  // Skip if already has spacing values
  $has_spacing = FALSE;
  if ($term->hasField('in_row_spacing_cm') && !$term->get('in_row_spacing_cm')->isEmpty()) {
    $has_spacing = TRUE;
  }
  
  if ($has_spacing) {
    $skipped++;
    continue;
  }
  
  // Get crop family
  $family_name = null;
  if ($term->hasField('crop_family') && !$term->get('crop_family')->isEmpty()) {
    $family = $term->get('crop_family')->entity;
    if ($family) {
      $family_name = $family->label();
    }
  }
  
  // Determine spacing values
  $spacing = null;
  
  // Try pattern matching first (e.g., "Cabbage - January King" matches "Cabbage")
  if ($family_name && isset($family_defaults[$family_name]['patterns'])) {
    foreach ($family_defaults[$family_name]['patterns'] as $pattern => $values) {
      if (stripos($term_name, $pattern) === 0 || stripos($term_name, $pattern . ' ') !== false) {
        $spacing = $values;
        break;
      }
    }
  }
  
  // Fall back to family default
  if (!$spacing && $family_name && isset($family_defaults[$family_name]['default'])) {
    $spacing = $family_defaults[$family_name]['default'];
  }
  
  // Fall back to global default
  if (!$spacing) {
    $spacing = $family_defaults['default'];
  }
  
  // Set the values
  $term->set('in_row_spacing_cm', $spacing['in_row']);
  $term->set('between_row_spacing_cm', $spacing['between_row']);
  $term->set('planting_method', $spacing['method']);
  $term->save();
  
  $updated++;
  
  // Track by family for summary
  if (!isset($by_family[$family_name ?: 'Unknown'])) {
    $by_family[$family_name ?: 'Unknown'] = 0;
  }
  $by_family[$family_name ?: 'Unknown']++;
  
  // Progress indicator
  if ($updated % 50 == 0) {
    echo ".";
  }
}

echo "\n\n=== SUMMARY ===\n";
echo "Updated: $updated terms\n";
echo "Skipped (already has values): $skipped terms\n\n";

echo "By Family:\n";
arsort($by_family);
foreach ($by_family as $family => $count) {
  echo sprintf("  %-20s %4d terms\n", $family, $count);
}

echo "\n✓ Spacing values have been set!\n";
echo "\nTo verify, check a few examples:\n";
echo "  ./vendor/bin/drush sql:query \"SELECT name, field_in_row_spacing_cm_value, field_between_row_spacing_cm_value, field_planting_method_value FROM taxonomy_term_field_data ttfd LEFT JOIN taxonomy_term__field_in_row_spacing_cm ir ON ttfd.tid=ir.entity_id LEFT JOIN taxonomy_term__field_between_row_spacing_cm br ON ttfd.tid=br.entity_id LEFT JOIN taxonomy_term__field_planting_method pm ON ttfd.tid=pm.entity_id WHERE ttfd.vid='plant_type' AND name LIKE 'Cabbage%' LIMIT 5;\"\n";
