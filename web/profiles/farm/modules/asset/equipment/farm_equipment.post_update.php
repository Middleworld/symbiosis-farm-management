<?php

/**
 * @file
 * Post update hooks for the farm_equipment module.
 */

declare(strict_types=1);

/**
 * Add "Equipment type" field to Equipment assets.
 */
function farm_equipment_post_update_install_equipment_type(&$sandbox) {

  // Install the farm_equipment_type taxonomy module.
  if (!\Drupal::service('module_handler')->moduleExists('farm_equipment_type')) {
    \Drupal::service('module_installer')->install(['farm_equipment_type']);
  }

  // Install the equipment_type reference field on equipment assets.
  $options = [
    'type' => 'entity_reference',
    'label' => t('Equipment type'),
    'description' => t("Enter the type of equipment."),
    'target_type' => 'taxonomy_term',
    'target_bundle' => 'equipment_type',
    'auto_create' => TRUE,
    'weight' => [
      'form' => -90,
      'view' => -50,
    ],
  ];
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition($options);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition('equipment_type', 'asset', 'farm_equipment', $field_definition);
}
