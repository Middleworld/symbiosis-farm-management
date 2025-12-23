<?php

/**
 * Add spacing and planting method fields to plant_type taxonomy.
 * 
 * This script creates three new fields:
 * 1. in_row_spacing_cm (decimal)
 * 2. between_row_spacing_cm (decimal)
 * 3. planting_method (list)
 * 
 * Run: ./vendor/bin/drush php:script add_spacing_fields.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

echo "Adding spacing fields to plant_type vocabulary...\n\n";

// Field 1: In-Row Spacing (cm)
echo "1. Creating in_row_spacing_cm field...\n";

$field_storage = FieldStorageConfig::loadByName('taxonomy_term', 'in_row_spacing_cm');
if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'in_row_spacing_cm',
    'entity_type' => 'taxonomy_term',
    'type' => 'decimal',
    'settings' => [
      'precision' => 5,
      'scale' => 1,
    ],
  ]);
  $field_storage->save();
  echo "   ✓ Field storage created\n";
} else {
  echo "   - Field storage already exists\n";
}

$field = FieldConfig::loadByName('taxonomy_term', 'plant_type', 'in_row_spacing_cm');
if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => 'plant_type',
    'label' => 'In-Row Spacing (cm)',
    'description' => 'Distance between plants within a row, measured in centimeters',
    'required' => FALSE,
    'settings' => [
      'min' => 1,
      'max' => 200,
    ],
  ]);
  $field->save();
  echo "   ✓ Field instance created\n";
} else {
  echo "   - Field instance already exists\n";
}

// Field 2: Between-Row Spacing (cm)
echo "\n2. Creating between_row_spacing_cm field...\n";

$field_storage = FieldStorageConfig::loadByName('taxonomy_term', 'between_row_spacing_cm');
if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'between_row_spacing_cm',
    'entity_type' => 'taxonomy_term',
    'type' => 'decimal',
    'settings' => [
      'precision' => 5,
      'scale' => 1,
    ],
  ]);
  $field_storage->save();
  echo "   ✓ Field storage created\n";
} else {
  echo "   - Field storage already exists\n";
}

$field = FieldConfig::loadByName('taxonomy_term', 'plant_type', 'between_row_spacing_cm');
if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => 'plant_type',
    'label' => 'Between-Row Spacing (cm)',
    'description' => 'Distance between rows, measured in centimeters',
    'required' => FALSE,
    'settings' => [
      'min' => 1,
      'max' => 200,
    ],
  ]);
  $field->save();
  echo "   ✓ Field instance created\n";
} else {
  echo "   - Field instance already exists\n";
}

// Field 3: Planting Method
echo "\n3. Creating planting_method field...\n";

$field_storage = FieldStorageConfig::loadByName('taxonomy_term', 'planting_method');
if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'planting_method',
    'entity_type' => 'taxonomy_term',
    'type' => 'list_string',
    'settings' => [
      'allowed_values' => [
        'direct' => 'Direct Seeding',
        'transplant' => 'Transplanting',
        'both' => 'Both Methods',
      ],
    ],
    'cardinality' => 1,
  ]);
  $field_storage->save();
  echo "   ✓ Field storage created\n";
} else {
  echo "   - Field storage already exists\n";
}

$field = FieldConfig::loadByName('taxonomy_term', 'plant_type', 'planting_method');
if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => 'plant_type',
    'label' => 'Planting Method',
    'description' => 'Primary planting method for this variety',
    'required' => FALSE,
  ]);
  $field->save();
  echo "   ✓ Field instance created\n";
} else {
  echo "   - Field instance already exists\n";
}

echo "\n✅ All spacing fields have been added to plant_type vocabulary!\n\n";

// Verify fields were created
echo "Verifying fields...\n";
$entity_field_manager = \Drupal::service('entity_field.manager');
$field_definitions = $entity_field_manager->getFieldDefinitions('taxonomy_term', 'plant_type');

$fields_to_check = ['in_row_spacing_cm', 'between_row_spacing_cm', 'planting_method'];
foreach ($fields_to_check as $field_name) {
  if (isset($field_definitions[$field_name])) {
    echo "   ✓ {$field_name} is available\n";
  } else {
    echo "   ✗ {$field_name} is MISSING\n";
  }
}

echo "\nNext steps:\n";
echo "1. Run: ./vendor/bin/drush field:info taxonomy_term plant_type\n";
echo "2. Populate spacing values with: ./vendor/bin/drush php:script set_default_spacing.php\n";
echo "3. Test API access to verify fields are exposed\n";
