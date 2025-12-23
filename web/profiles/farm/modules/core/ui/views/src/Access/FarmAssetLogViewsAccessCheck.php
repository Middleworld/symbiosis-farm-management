<?php

declare(strict_types=1);

namespace Drupal\farm_ui_views\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Checks access for displaying Views of logs that reference assets.
 */
class FarmAssetLogViewsAccessCheck implements AccessInterface {

  /**
   * The asset storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $assetStorage;

  /**
   * The log storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $logStorage;

  /**
   * FarmAssetLogViewsAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->assetStorage = $entity_type_manager->getStorage('asset');
    $this->logStorage = $entity_type_manager->getStorage('log');
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function access(RouteMatchInterface $route_match) {

    // Get the "asset" parameter and attempt to load the asset.
    // If the asset cannot be loaded, allow access so that Views contextual
    // filter validation returns a 404.
    $asset_id = $route_match->getParameter('asset');
    /** @var \Drupal\asset\Entity\AssetInterface|null $asset */
    $asset = $this->assetStorage->load($asset_id);
    if (is_null($asset)) {
      return AccessResult::allowed();
    }

    // Get the "log_type" parameter. If it is empty or "all", allow access so
    // that Views can handle it.
    $log_type = $route_match->getParameter('log_type');
    if (empty($log_type) || $log_type == 'all') {
      return AccessResult::allowed();
    }

    // Build a count query for logs of this type.
    $query = $this->logStorage->getAggregateQuery()
      ->accessCheck(TRUE)
      ->condition('type', $log_type)
      ->count();

    // Only include logs that reference the asset.
    $reference_condition = $query->orConditionGroup()
      ->condition('asset.entity.id', $asset_id)
      ->condition('location.entity.id', $asset_id);
    $query->condition($reference_condition);

    // Determine access based on the log count.
    $count = $query->execute();
    $access = AccessResult::allowedIf($count > 0);

    // Invalidate the access result when logs of this bundle are changed.
    $access->addCacheTags(["log_list:$log_type"]);
    return $access;
  }

}
