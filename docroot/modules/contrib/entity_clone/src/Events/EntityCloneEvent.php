<?php

namespace Drupal\entity_clone\Events;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Represents entity selection as event.
 */
class EntityCloneEvent extends Event {

  /**
   * Entity being cloned.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * New cloned entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $cloned_entity;

  /**
   * Properties.
   *
   * @var array
   */
  protected $properties;

  /**
   * Constructs an EntityCloneEvent object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Entity\EntityInterface $cloned_entity
   * @param array $properties
   */
  public function __construct(EntityInterface $entity, EntityInterface $cloned_entity, array $properties = []) {
    $this->entity = $entity;
    $this->cloned_entity = $cloned_entity;
    $this->properties = $properties;
  }

  /**
   * Gets entity being cloned.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets new cloned entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getClonedEntity() {
    return $this->cloned_entity;
  }

  /**
   * Gets entity properties.
   *
   * @return array
   */
  public function getProperties() {
    return $this->properties;
  }

}
