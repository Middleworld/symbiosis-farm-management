<?php

declare(strict_types=1);

namespace Drupal\asset\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that archives an asset.
 */
#[Action(
  id: 'asset_archive_action',
  label: new TranslatableMarkup('Archive an asset'),
  type: 'asset',
)]
class AssetArchive extends AssetStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'archived';

}
