<?php

/**
 * Import image for Courgette Black Beauty (organic) from Moles Seeds.
 * Product Code: VOG640
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;

// Term ID for Courgette Black Beauty (organic)
$term_id = 12911;
$product_code = 'VOG640';

// Load the term
$term = Term::load($term_id);
if (!$term) {
  echo "Term not found: $term_id\n";
  return;
}

echo "Processing: " . $term->getName() . " (Code: $product_code)\n";

// Construct the image URL
$image_url = "https://www.wholesale.molesseeds.co.uk/pics/" . $product_code . ".jpg";
echo "Fetching: $image_url\n";

// Fetch the image
$image_data = @file_get_contents($image_url);

if ($image_data === FALSE) {
  echo "  ❌ Failed to fetch image\n";
  return;
}

echo "  ✓ Image fetched (" . strlen($image_data) . " bytes)\n";

// Create a safe filename
$filename = strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $term->getName())) . '.jpg';
$directory = 'public://taxonomy/';

// Ensure directory exists
\Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

// Save the file
$file = file_save_data($image_data, $directory . $filename, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);

if ($file) {
  echo "  ✓ File saved: " . $file->getFilename() . "\n";
  
  // Attach to term
  $term->set('image', [
    'target_id' => $file->id(),
    'alt' => $term->getName(),
    'title' => $term->getName(),
  ]);
  
  $term->save();
  echo "  ✓ Image attached to term\n";
} else {
  echo "  ❌ Failed to save file\n";
}

echo "\nDone!\n";
