<?php

/**
 * Analyze plant type patterns to identify common crops for bulk spacing assignment
 */

use Drupal\taxonomy\Entity\Term;

// Load all plant_type terms
$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$terms = $storage->loadByProperties(['vid' => 'plant_type']);

// Group by crop family and analyze patterns
$families = [];
$crop_patterns = [];

foreach ($terms as $term) {
  $term_name = $term->label();
  
  // Get crop family
  $family_name = 'Unknown';
  if ($term->hasField('crop_family') && !$term->get('crop_family')->isEmpty()) {
    $family = $term->get('crop_family')->entity;
    if ($family) {
      $family_name = $family->label();
    }
  }
  
  // Store by family
  if (!isset($families[$family_name])) {
    $families[$family_name] = [];
  }
  $families[$family_name][] = $term_name;
  
  // Extract base crop type (e.g., "Cabbage" from "Cabbage - January King")
  $base_crop = preg_split('/\s*[-–:]\s*/', $term_name)[0];
  $base_crop = trim($base_crop);
  
  if (!isset($crop_patterns[$base_crop])) {
    $crop_patterns[$base_crop] = [
      'count' => 0,
      'family' => $family_name,
      'examples' => []
    ];
  }
  $crop_patterns[$base_crop]['count']++;
  if (count($crop_patterns[$base_crop]['examples']) < 3) {
    $crop_patterns[$base_crop]['examples'][] = $term_name;
  }
}

// Sort by count to find most common base crops
uasort($crop_patterns, function($a, $b) {
  return $b['count'] - $a['count'];
});

echo "\n=== CROP FAMILIES ===\n";
foreach ($families as $family => $terms) {
  echo sprintf("\n%s (%d varieties)\n", $family, count($terms));
  $samples = array_slice($terms, 0, 5);
  foreach ($samples as $sample) {
    echo "  - $sample\n";
  }
  if (count($terms) > 5) {
    echo "  ... and " . (count($terms) - 5) . " more\n";
  }
}

echo "\n\n=== COMMON BASE CROPS (for pattern matching) ===\n";
echo "Showing crops with 5+ varieties:\n\n";

foreach ($crop_patterns as $base_crop => $data) {
  if ($data['count'] >= 5) {
    echo sprintf("%-30s %3d varieties [%s]\n", $base_crop, $data['count'], $data['family']);
    foreach ($data['examples'] as $example) {
      echo "    e.g., $example\n";
    }
  }
}

echo "\n\n=== SUGGESTED PATTERN GROUPS ===\n";
echo "These patterns can be used for bulk spacing assignment:\n\n";

// Define common crop groups
$suggested_groups = [
  'Brassicas' => ['Cabbage', 'Cauliflower', 'Broccoli', 'Brussels Sprout', 'Kale'],
  'Leafy Greens' => ['Lettuce', 'Spinach', 'Chard', 'Rocket'],
  'Root Vegetables' => ['Carrot', 'Beetroot', 'Parsnip', 'Turnip'],
  'Alliums' => ['Onion', 'Leek', 'Garlic'],
  'Legumes' => ['Pea', 'Bean'],
  'Cucurbits' => ['Cucumber', 'Courgette', 'Pumpkin', 'Squash'],
  'Herbs' => ['Basil', 'Parsley', 'Coriander', 'Dill']
];

foreach ($suggested_groups as $group => $patterns) {
  $matches = [];
  foreach ($patterns as $pattern) {
    if (isset($crop_patterns[$pattern])) {
      $matches[] = sprintf("%s (%d)", $pattern, $crop_patterns[$pattern]['count']);
    }
  }
  if (!empty($matches)) {
    echo "$group: " . implode(', ', $matches) . "\n";
  }
}

echo "\n✓ Analysis complete!\n";
