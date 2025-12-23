<?php

/**
 * @file
 * Provides Views runtime hooks for farm_land.module.
 */

declare(strict_types=1);

use Drupal\farm_land\Entity\FarmLandType;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_render().
 */
function farm_land_views_pre_render(ViewExecutable $view) {

  // Add land type map layers to the land assets map.
  if ($view->id() == 'farm_asset' && $view->current_display == 'page_type' && !empty($view->args[0]) && $view->args[0] == 'land') {

    // If the asset_map has not been added, bail.
    if (empty($view->attachment_before['asset_map'])) {
      return;
    }
    $map = &$view->attachment_before['asset_map'];

    // Load all land types.
    $land_types = FarmLandType::loadMultiple();

    // Get exposed filters.
    $exposed_filters = $view->getExposedInput();

    // If the land type exposed filter is already in use remove land types
    // that are not specified by the filter value.
    if (isset($exposed_filters['land_type_value'])) {
      $land_type_filters = (array) $exposed_filters['land_type_value'];
      $land_types = array_intersect_key($land_types, $land_type_filters);
    }

    // Create a layer for each land type.
    $asset_layers = [];
    foreach ($land_types as $land_type) {
      /** @var \Drupal\farm_map\Entity\LayerStyleInterface|null $layer_style */
      if ($layer_style = \Drupal::service('farm_map.layer_style_loader')->load(['asset_type' => 'land', 'land_type' => $land_type->id()])) {
        $color = $layer_style->get('color');
      }
      $asset_layers['land_' . $land_type->id()] = [
        'group' => t('Land types'),
        'label' => $land_type->label(),
        'asset_type' => 'land',
        'filters' => $exposed_filters + ['land_type_value[]' => $land_type->id()],
        'color' => $color ?? 'orange',
        'zoom' => TRUE,
      ];
    }

    // Add layers to the map settings.
    $map['#map_settings']['asset_type_layers'] = array_merge($map['#map_settings']['asset_type_layers'], $asset_layers);

    // Remove the land asset layer.
    unset($map['#map_settings']['asset_type_layers']['full_land']);
  }
}
