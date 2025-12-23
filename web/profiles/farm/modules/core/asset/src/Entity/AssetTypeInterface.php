<?php

declare(strict_types=1);

namespace Drupal\asset\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface for defining asset type entities.
 */
interface AssetTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * Gets the asset type's workflow ID.
   *
   * Used by the $asset->status field.
   *
   * @return string
   *   The asset type workflow ID.
   */
  public function getWorkflowId();

  /**
   * Sets the workflow ID of the asset type.
   *
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return $this
   */
  public function setWorkflowId($workflow_id);

}
