<?php

namespace Drupal\entity_clone\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\entity_clone\Events\EntityCloneEvent;
use Drupal\entity_clone\Events\EntityCloneEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityReferenceRevisions implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * EntityCloned constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    $events[EntityCloneEvents::PRE_SAVE][] = ['onEntityCloned'];
    return $events;
  }

  /**
   * Clones entity reference revisions fields.
   *
   * @param \Drupal\entity_clone\Events\EntityCloneEvent $event
   */
  public function onEntityCloned(EntityCloneEvent $event) {
    $cloned_entity = $event->getClonedEntity();

    foreach ($cloned_entity->getFieldDefinitions() as $field_id => $field_definition) {
      // Always recurse for ERR fields.
      if ($field_definition->getType() == 'entity_reference_revisions') {
        $new_referenced_entities = [];
        foreach ($cloned_entity->get($field_id)->referencedEntities() as $referenced_entity) {
          if ($this->entityTypeManager->hasHandler($this->entityTypeManager->getDefinition($referenced_entity->getEntityTypeId())->id(), 'entity_clone')) {
            $clone_handler = $this->entityTypeManager->getHandler($this->entityTypeManager->getDefinition($referenced_entity->getEntityTypeId())->id(), 'entity_clone');
            $cloned_reference_entity = $referenced_entity->createDuplicate();
            $cloned_reference_entity->isDefaultRevision(TRUE);

            // Recursively clone nested entities
            $new_referenced_entity = $clone_handler->cloneEntity($referenced_entity, $cloned_reference_entity);

            $new_referenced_entities[] = [
              'target_id' => $new_referenced_entity->id(),
              'target_revision_id' => $new_referenced_entity->getRevisionId(),
            ];

            $cloned_entity->set($field_id, $new_referenced_entities);
          }
        }
      }
    }
  }
}
