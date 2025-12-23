<?php

declare(strict_types=1);

namespace Drupal\farm_inventory\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'inventory' formatter.
 */
#[FieldFormatter(
  id: 'inventory',
  label: new TranslatableMarkup('Inventory'),
  field_types: ['inventory'],
)]
class InventoryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\farm_inventory\Plugin\Field\FieldType\InventoryItem $item */
    foreach ($items as $delta => $item) {
      $summary = $item->value;
      if (!empty($item->units)) {
        $summary .= ' ' . $item->units;
      }
      if (!empty($item->measure)) {
        $measures = quantity_measures();
        if (!empty($measures[$item->measure]['label'])) {
          $summary .= ' (' . $measures[$item->measure]['label'] . ')';
        }
      }
      $elements[$delta]['value'] = ['#markup' => $summary];
    }

    return $elements;
  }

}
