<?php

declare(strict_types=1);

namespace Drupal\farm_timeline\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Timeline task data type.
 */
#[DataType(
  id: 'farm_timeline_task',
  label: new TranslatableMarkup('Timeline Task'),
  definition_class: '\Drupal\farm_timeline\TypedData\TimelineTaskDefinition',
)]
class TimelineTask extends Map implements ComplexDataInterface {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // PHPStan level 3+ throws the following error on the next line:
    // Result of && is always false.
    // We ignore this because we are following Drupal core's pattern.
    // @phpstan-ignore booleanAnd.alwaysFalse
    if (isset($values) && !is_array($values)) {
      throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
    }

    // Inherit resource_id from Row parent ID.
    if (!isset($values['resource_id']) && $this->parent instanceof ItemList && $this->parent->parent instanceof TimelineRow) {
      $values['resource_id'] = $this->parent->parent->get('id')->getValue();
    }

    // Set default values.
    $values += [
      'draggable' => FALSE,
      'resizable' => FALSE,
    ];
    parent::setValue($values);
  }

}
