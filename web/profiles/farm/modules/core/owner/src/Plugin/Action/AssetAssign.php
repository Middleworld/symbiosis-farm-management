<?php

declare(strict_types=1);

namespace Drupal\farm_owner\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Action that assigns users to assets.
 */
#[Action(
  id: 'asset_assign_action',
  label: new TranslatableMarkup('Assign assets to users.'),
  type: 'asset',
  confirm_form_route_name: 'farm_owner.asset_assign_action_form',
)]
class AssetAssign extends AssignBase {

}
