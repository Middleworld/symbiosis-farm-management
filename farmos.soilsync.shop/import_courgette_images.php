<?php

/**
 * Import images for courgette varieties from Moles Seeds
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;

$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$terms = $storage->loadByProperties(['vid' => 'plant_type']);

echo "Checking courgette varieties for images...\n\n";

$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($terms as $term) {
  $name = $term->label();
  
  if (stripos($name, 'Courgette') !== 0) {
    continue;
  }
  
  // Check if already has image
  if ($term->hasField('image') && !$term->get('image')->isEmpty()) {
    $skipped++;
    continue;
  }
  
  // Get description to extract product code
  $description = '';
  if ($term->hasField('description') && !$term->get('description')->isEmpty()) {
    $description = $term->get('description')->value;
  }
  
  // Extract product code (format: Code: ABC123)
  if (preg_match('/Code:\s*([A-Z0-9]+)/i', $description, $matches)) {
    $code = $matches[1];
    $url = "https://www.wholesale.molesseeds.co.uk/pics/{$code}.jpg";
    
    echo "Processing: $name (code: $code)... ";
    
    // Try to fetch the image
    $image_data = @file_get_contents($url);
    
    if ($image_data) {
      // Save the image
      $directory = 'private://farm/term/' . date('Y-m');
      \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
      
      $filename = preg_replace('/[^a-z0-9_\-]/', '_', strtolower($name)) . '.jpg';
      $file_path = $directory . '/' . $filename;
      
      $file = \Drupal::service('file.repository')->writeData($image_data, $file_path, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
      
      if ($file) {
        $term->set('image', ['target_id' => $file->id()]);
        $term->save();
        echo "✓ Image saved\n";
        $updated++;
      } else {
        echo "✗ Failed to save file\n";
        $failed++;
      }
    } else {
      echo "✗ Image not found at $url\n";
      $failed++;
    }
  } else {
    echo "Skipping $name - no product code found in description\n";
    $failed++;
  }
}

echo "\n=== SUMMARY ===\n";
echo "Updated: $updated courgettes\n";
echo "Skipped (already has image): $skipped\n";
echo "Failed: $failed\n";
