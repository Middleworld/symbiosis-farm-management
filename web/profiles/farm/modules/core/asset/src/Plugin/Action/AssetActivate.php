<?php

declare(strict_types=1);

namespace Drupal\asset\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that makes an asset active.
 */
#[Action(
  id: 'asset_activate_action',
  label: new TranslatableMarkup('Makes an Asset active'),
  type: 'asset',
)]
class AssetActivate extends AssetStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'active';

}
