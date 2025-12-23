<?php

$query = \Drupal::entityQuery('taxonomy_term')
  ->condition('vid', 'plant_type')
  ->accessCheck(FALSE);
$tids = $query->execute();

$terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($tids);
echo "Found " . count($terms) . " total terms\n";
$families = [];
$checked = 0;
foreach ($terms as $term) {
  $checked++;
  if ($checked > 100) break; // Limit output
  $cf = $term->get('crop_family');
  if ($cf && $cf->entity) {
    $family = $cf->entity->getName();
    if (!in_array($family, $families)) {
      $families[] = $family;
      echo "Found family: $family\n";
    }
  }
}
sort($families);
echo 'Available crop families: ' . implode(', ', $families) . PHP_EOL;