<?php

/**
 * Bulk import images from Moles Seeds for plant type terms.
 */

use Drupal\Core\File\FileSystemInterface;

$crop_family_filter = 'Amaranthaceae'; // Change this for different crop families

$terms = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(["vid" => "plant_type"]);

$batch_size = 500; // Process in batches

$count = 0;

foreach ($terms as $term) {

  $cf = $term->get('crop_family');

  if ($cf && $cf->entity && $cf->entity->getName() == $crop_family_filter) {

    // Skip if already has image
    if (!$term->get("image")->isEmpty()) {
      continue;
    }

    $desc = $term->get("description")->value ?? '';

    if (preg_match('/Code: ([A-Z0-9]+)/', $desc, $matches)) {

      $code = $matches[1];

      // Directly get image from Moles Seeds

      $img_url = "https://www.wholesale.molesseeds.co.uk/pics/{$code}.jpg";

      echo "Image URL: $img_url\n";

      $data = @file_get_contents($img_url);

      if ($data && strlen($data) > 1000) {

        $filename = $code . '.jpg';

        $file = \Drupal::service('file.repository')->writeData($data, "private://farm/term/2025-09/{$filename}", FileSystemInterface::EXISTS_REPLACE);

        if ($file) {

          $term->set("image", $file->id());

          $term->save();

          echo "Saved image for " . $term->label() . " from Moles Seeds\n";

          $count++;

          if ($count >= $batch_size) {

            echo "Batch of $batch_size processed. Run again for more.\n";

            exit;

          }

        } else {

          echo "Failed to save file for " . $term->label() . "\n";

        }

      } else {

        echo "No valid image data for " . $term->label() . " at $img_url\n";

      }

    } else {

      echo "No code found in description for " . $term->label() . "\n";

    }

  }

}

echo "Processed $count terms.\n";