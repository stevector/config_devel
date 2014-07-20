<?php

/**
 * @file
 * Contains Drupal\config_devel\EventSubscriber\ConfigDevelFileStorageSubscriber.
 */

namespace Drupal\config_devel\EventSubscriber;

use Drupal\Core\Config\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Exception\DumpException;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\ConfigEvents;

/**
 * ConfigDevelFileStorageSubscriber subscriber for configuration CRUD events.
 */
class ConfigDevelAutoExportSubscriber extends ConfigDevelSubscriberBase implements EventSubscriberInterface {

  /**
   * The files to automatically export.
   *
   * @var array
   */
  protected $autoExportFiles;

  /**
   * React to configuration ConfigEvent::SAVE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $this->autoExportConfig($event->getConfig());
  }

  /**
   * React to configuration ConfigEvent::RENAME events.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The event to process.
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $this->autoExportConfig($event->getConfig());
  }

  protected function autoExportConfig(Config $config) {
    $config_name = $config->getName();
    $auto_export = $this->configFactory->get('config_devel.settings')->get('auto_export');
    $file_names = array_keys(array_intersect($auto_export, array($config_name)));
    if ($file_names) {
      $data = $config->get();
      if ($entity_type_id = $this->configManager->getEntityTypeIdByName($config_name)) {
        try {
          $entity_storage = $this->getStorage($entity_type_id);
          $entity_id = $this->getEntityId($entity_storage, $config_name);
          $id_key = $entity_storage->getEntityType()->getKey('id');
          // \Drupal\Core\Config\Entity\ConfigEntityBase::toArray() uses id().
          $empty_entity = $entity_storage->create(array($id_key => $entity_id));
          $empty_data = $empty_entity->toArray();
          foreach ($empty_data as $k => $v) {
            if ($k !== $id_key && $data[$k] === $v) {
              unset($data[$k]);
            }
          }
        }
        catch (\Exception $e) {
          // Cleanup is non-essential so any problems we can skip.
        }
        unset($data['uuid']);
      }
      foreach ($file_names as $filename) {
        try {
          file_put_contents($filename, $this->fileStorage->encode($data));
        }
        catch (DumpException $e) {
          // Do nothing. What could we do?
        }
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 10);
    $events[ConfigEvents::RENAME][] = array('onConfigRename', 10);
    return $events;
  }

}
