<?php

/**
 * @file
 * Provides Views runtime hooks for farm_structure.module.
 */

declare(strict_types=1);

use Drupal\farm_structure\Entity\FarmStructureType;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_render().
 */
function farm_structure_views_pre_render(ViewExecutable $view) {

  // Add structure type map layers to the structure assets map.
  if ($view->id() == 'farm_asset' && $view->current_display == 'page_type' && !empty($view->args[0]) && $view->args[0] == 'structure') {

    // If the asset_map has not been added, bail.
    if (empty($view->attachment_before['asset_map'])) {
      return;
    }
    $map = &$view->attachment_before['asset_map'];

    // Load all structure types.
    $structure_types = FarmStructureType::loadMultiple();

    // Get exposed filters.
    $exposed_filters = $view->getExposedInput();

    // If the structure type exposed filter is already in use remove structure
    // types that are not specified in the filter value.
    if (isset($exposed_filters['structure_type_value'])) {
      $structure_type_filters = (array) $exposed_filters['structure_type_value'];
      $structure_types = array_intersect_key($structure_types, $structure_type_filters);
    }

    // Create a layer for each structure type.
    $asset_layers = [];
    foreach ($structure_types as $structure_type) {
      /** @var \Drupal\farm_map\Entity\LayerStyleInterface|null $layer_style */
      if ($layer_style = \Drupal::service('farm_map.layer_style_loader')->load(['asset_type' => 'structure', 'structure_type' => $structure_type->id()])) {
        $color = $layer_style->get('color');
      }
      $asset_layers['structure_' . $structure_type->id()] = [
        'group' => t('Structure types'),
        'label' => $structure_type->label(),
        'asset_type' => 'structure',
        'filters' => $exposed_filters + ['structure_type_value[]' => $structure_type->id()],
        'color' => $color ?? 'orange',
        'zoom' => TRUE,
      ];
    }

    // Add layers to the map settings.
    $map['#map_settings']['asset_type_layers'] = array_merge($map['#map_settings']['asset_type_layers'], $asset_layers);

    // Remove the structure asset layer.
    unset($map['#map_settings']['asset_type_layers']['full_structure']);
  }
}
