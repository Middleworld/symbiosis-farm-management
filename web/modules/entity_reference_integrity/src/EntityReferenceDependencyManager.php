<?php

namespace Drupal\entity_reference_integrity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Calculate entity dependencies based on entity reference fields.
 *
 * @deprecated in entity_reference_integrity:8.x-1.2 and is removed from
 *   entity_reference_integrity:2.0.0. Use the entity_reference_integrity entity
 *   handler instead.
 * @see https://www.drupal.org/node/3509671
 * @see https://www.drupal.org/project/entity_reference_integrity/issues/3509653
 */
class EntityReferenceDependencyManager implements EntityReferenceDependencyManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create an EntityReferenceDependencyManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDependents(EntityInterface $entity) {
    return $this->entityTypeManager
      ->getHandler($entity->getEntityTypeId(), 'entity_reference_integrity')
      ->hasDependents($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentEntityIds(EntityInterface $entity) {
    return $this->entityTypeManager
      ->getHandler($entity->getEntityTypeId(), 'entity_reference_integrity')
      ->getDependentEntityIds($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentEntities(EntityInterface $entity) {
    return $this->entityTypeManager
      ->getHandler($entity->getEntityTypeId(), 'entity_reference_integrity')
      ->getDependentEntities($entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function getAccessDeniedReason(EntityInterface $entity, bool $translate = TRUE) {
    return EntityReferenceIntegrityEntityHandler::getAccessDeniedReason($entity, $translate);
  }

}
