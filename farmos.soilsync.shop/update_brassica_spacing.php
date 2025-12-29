<?php

/**
 * Update all brassica varieties to standard spacing
 */

use Drupal\taxonomy\Entity\Term;

$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$terms = $storage->loadByProperties(['vid' => 'plant_type']);

$brassicas = ['Broccoli', 'Kale', 'Brussels Sprout', 'Kohlrabi', 'Calabrese'];
$updated = [];

foreach ($terms as $term) {
  $name = $term->label();
  foreach ($brassicas as $brassica) {
    if (stripos($name, $brassica) === 0 || stripos($name, $brassica . ' ') !== false) {
      $term->set('in_row_spacing_cm', 45);
      $term->set('between_row_spacing_cm', 40);
      $term->set('planting_method', 'transplant');
      $term->save();
      if (!isset($updated[$brassica])) {
        $updated[$brassica] = 0;
      }
      $updated[$brassica]++;
      break;
    }
  }
}

echo "✓ Updated brassicas to standard spacing (45cm in-row, 40cm between-row):\n\n";
foreach ($updated as $type => $count) {
  echo "  - $type: $count varieties\n";
}
$total = array_sum($updated);
echo "\nTotal: $total varieties updated\n";
