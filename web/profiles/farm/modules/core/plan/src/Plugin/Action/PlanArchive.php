<?php

declare(strict_types=1);

namespace Drupal\plan\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that archives a plan.
 */
#[Action(
  id: 'plan_archive_action',
  label: new TranslatableMarkup('Archive a plan'),
  type: 'plan',
)]
class PlanArchive extends PlanStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'archived';

}
