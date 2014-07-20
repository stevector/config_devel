<?php

/**
 * @file
 * Contains Drupal\config_devel\EventSubscriber\ConfigDevelFileStorageSubscriber.
 */

namespace Drupal\config_devel\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Exception\DumpException;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\ConfigEvents;

/**
 * ConfigDevelFileStorageSubscriber subscriber for configuration CRUD events.
 */
class ConfigDevelAutoExportSubscriber implements EventSubscriberInterface {

  /**
   * The files to automatically export.
   *
   * @var array
   */
  protected $autoExportFiles;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs the ConfigDevelAutoExportSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigManagerInterface $config_manager, FileStorage $file_storage) {
    $this->fileStorage = $file_storage;
    $this->configFactory = $config_factory;
    $this->configManager = $config_manager;
  }

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
        /** @var $entity_storage \Drupal\Core\Config\Entity\ConfigEntityStorageInterface */
        $entity_storage = $this->configManager->getEntityManager()->getStorage($entity_type_id);
        $entity_id = $entity_storage::getIDFromConfigName($config_name, substr($entity_storage->getConfigPrefix(), 0, -1));
        $id_key = $entity_storage->getEntityType()->getKey('id');
        $empty_entity = $entity_storage->create(array($id_key => $entity_id));
        $empty_data = $empty_entity->toArray();
        foreach ($empty_data as $k => $v) {
          if ($k !== $id_key && $data[$k] === $v) {
            unset($data[$k]);
          }
        }
        unset($data['uuid']);
      }
      foreach ($file_names as $filename) {
        try {
          file_put_contents($filename, $this->fileStorage->encode($data));
        } catch (DumpException $e) {
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
