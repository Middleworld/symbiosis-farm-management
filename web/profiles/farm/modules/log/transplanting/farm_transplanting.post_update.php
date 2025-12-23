<?php

/**
 * @file
 * Post update hooks for the farm_transplanting module.
 */

declare(strict_types=1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Restore missing transplant_days field.
 */
function farm_transplanting_post_update_restore_transplant_days() {

  // A previous update hook in the farm_plant_type module moved the field to
  // this module, but the update logic was flawed and caused the field to be
  // deleted in some cases. We only proceed if that update hook ran.
  $update_list = \Drupal::keyValue('post_update')->get('existing_updates');
  if (!in_array('farm_plant_type_post_update_move_transplant_days', $update_list)) {
    return;
  }

  // Install the transplant_days field storage config and field config if they
  // do not exist.
  if (is_null(FieldStorageConfig::load('taxonomy_term.transplant_days'))) {
    $field_storage = FieldStorageConfig::create([
      'id' => 'taxonomy_term.transplant_days',
      'field_name' => 'transplant_days',
      'entity_type' => 'taxonomy_term',
      'type' => 'integer',
      'settings' => [
        'unsigned' => TRUE,
        'size' => 'normal',
      ],
      'module' => 'core',
      'locked' => FALSE,
      'cardinality' => 1,
      'indexes' => [],
      'persist_with_no_fields' => FALSE,
      'custom_storage' => FALSE,
      'dependencies' => [
        'enforced' => [
          'module' => [
            'farm_transplanting',
          ],
        ],
        'module' => [
          'taxonomy',
        ],
      ],
    ]);
    $field_storage->save();
  }
  if (is_null(FieldConfig::load('taxonomy_term.plant_type.transplant_days'))) {
    $field = FieldConfig::create([
      'id' => 'taxonomy_term.plant_type.transplant_days',
      'field_name' => 'transplant_days',
      'entity_type' => 'taxonomy_term',
      'bundle' => 'plant_type',
      'label' => 'Days to transplant',
      'description' => '',
      'required' => FALSE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'min' => 1,
        'max' => NULL,
        'prefix' => '',
        'suffix' => ' day| days',
      ],
      'field_type' => 'integer',
      'dependencies' => [
        'enforced' => [
          'module' => [
            'farm_transplanting',
          ],
        ],
        'module' => [
          'taxonomy',
        ],
      ],
    ]);
    $field->save();
  }
}
