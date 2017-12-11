<?php

namespace Drupal\entity_clone\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\entity_clone\Events\EntityCloneEvent;
use Drupal\entity_clone\Events\EntityCloneEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FieldCollectionItem implements EventSubscriberInterface {

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
    // Field Collections need the host entity saved, so it uses POST_SAVE event.
    $events[EntityCloneEvents::POST_SAVE][] = ['onEntityCloned'];
    return $events;
  }

  /**
   * Clones field-collection fields.
   *
   * @param \Drupal\entity_clone\Events\EntityCloneEvent $event
   */
  public function onEntityCloned(EntityCloneEvent $event) {
    // Escape if 'field_collection_item' entity doesn't exist.
    if (!$exists = \Drupal::service('entity_type.manager')->getDefinition('field_collection_item', FALSE)) {
      return;
    }
    
    $storage_field_collection = $this->entityTypeManager->getStorage('field_collection_item');
    $cloned_entity = $event->getClonedEntity();

    foreach ($cloned_entity->getFieldDefinitions() as $field_id => $field_definition) {
      if ($field_definition->getType() == 'field_collection') {
        $items = $cloned_entity->get($field_id)->getValue();

        // Delete reference to current items
        $cloned_entity->{$field_id} = array();

        foreach ($items as $delta => $referenced_entity) {
          $field_collection_item = $storage_field_collection->load($referenced_entity['value']);

          if ($field_collection_item->id()) {
            // Do not save when the host entity is being deleted. See
            // \Drupal\field_collection\Plugin\Field\FieldType\FieldCollection::delete().
            if (empty($cloned_entity->field_collection_deleting)) {
              $clone_handler = $this->entityTypeManager->getHandler($this->entityTypeManager->getDefinition($field_collection_item->getEntityTypeId())->id(), 'entity_clone');
              $cloned_reference_entity = $field_collection_item->createDuplicate();

              // Assign Field collection to the host entity, and save the host.
              $cloned_reference_entity->setHostEntity($cloned_entity);

              // Recursively clone nested entities
              $clone_handler->cloneEntity($field_collection_item, $cloned_reference_entity);
            }
          }
        }
      }
    }
  }
}
