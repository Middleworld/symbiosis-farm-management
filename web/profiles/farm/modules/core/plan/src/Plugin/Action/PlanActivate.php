<?php

declare(strict_types=1);

namespace Drupal\plan\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that marks a plan as active.
 */
#[Action(
  id: 'plan_activate_action',
  label: new TranslatableMarkup('Makes a Plan active'),
  type: 'plan',
)]
class PlanActivate extends PlanStateChangeBase {

  /**
   * {@inheritdoc}
   */
  protected $targetState = 'active';

}
