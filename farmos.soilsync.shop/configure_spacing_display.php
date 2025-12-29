<?php

/**
 * Configure spacing fields to display on plant_type taxonomy term pages
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

echo "Configuring spacing fields display...\n\n";

// Load or create the default view display for plant_type
$view_display = EntityViewDisplay::load('taxonomy_term.plant_type.default');

if (!$view_display) {
  echo "Creating default view display for plant_type...\n";
  $view_display = EntityViewDisplay::create([
    'targetEntityType' => 'taxonomy_term',
    'bundle' => 'plant_type',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}

// Get current components
$components = $view_display->getComponents();
echo "Current components: " . count($components) . "\n";

// Add spacing fields to display
$spacing_fields = [
  'in_row_spacing_cm' => [
    'label' => 'above',
    'type' => 'number_decimal',
    'settings' => [
      'thousand_separator' => '',
      'decimal_separator' => '.',
      'scale' => 1,
      'prefix_suffix' => TRUE,
    ],
    'weight' => 10,
    'region' => 'content',
  ],
  'between_row_spacing_cm' => [
    'label' => 'above',
    'type' => 'number_decimal',
    'settings' => [
      'thousand_separator' => '',
      'decimal_separator' => '.',
      'scale' => 1,
      'prefix_suffix' => TRUE,
    ],
    'weight' => 11,
    'region' => 'content',
  ],
  'planting_method' => [
    'label' => 'above',
    'type' => 'list_default',
    'settings' => [],
    'weight' => 12,
    'region' => 'content',
  ],
];

foreach ($spacing_fields as $field_name => $config) {
  echo "Adding $field_name to display...\n";
  $view_display->setComponent($field_name, $config);
}

// Save the display configuration
$view_display->save();

echo "\n✓ Display configuration saved!\n";
echo "\nThe spacing fields should now be visible when viewing plant_type terms.\n";
echo "Visit: /taxonomy/term/[term-id] to see the fields displayed.\n\n";

// Also configure the form display
echo "Configuring form display...\n";

$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('taxonomy_term.plant_type.default');

if (!$form_display) {
  echo "Creating default form display for plant_type...\n";
  $form_display = \Drupal::entityTypeManager()
    ->getStorage('entity_form_display')
    ->create([
      'targetEntityType' => 'taxonomy_term',
      'bundle' => 'plant_type',
      'mode' => 'default',
      'status' => TRUE,
    ]);
}

// Configure form widgets
$form_widgets = [
  'in_row_spacing_cm' => [
    'type' => 'number',
    'weight' => 10,
    'settings' => [
      'placeholder' => 'e.g. 30',
    ],
    'region' => 'content',
  ],
  'between_row_spacing_cm' => [
    'type' => 'number',
    'weight' => 11,
    'settings' => [
      'placeholder' => 'e.g. 45',
    ],
    'region' => 'content',
  ],
  'planting_method' => [
    'type' => 'options_select',
    'weight' => 12,
    'settings' => [],
    'region' => 'content',
  ],
];

foreach ($form_widgets as $field_name => $config) {
  echo "Adding $field_name to form...\n";
  $form_display->setComponent($field_name, $config);
}

$form_display->save();

echo "\n✓ Form display configuration saved!\n";
echo "You can now edit plant_type terms and see the spacing fields in the form.\n";

// Clear caches
\Drupal::service('plugin.manager.field.formatter')->clearCachedDefinitions();
\Drupal::service('plugin.manager.field.widget')->clearCachedDefinitions();
drupal_flush_all_caches();

echo "\n✓ Caches cleared!\n";
