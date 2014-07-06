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
class ConfigDevelWriteBackSubscriber implements EventSubscriberInterface {

  /**
   * The files to write back.
   *
   * @var array
   */
  protected $writeBack;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * List of config objects to write back to extension default config.
   *
   * @var array
   */
  protected $defaultConfigToWriteBack;

  /**
   * Constructs the ConfigDevelFileStorageSubscriber object.
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
    $this->writeBackConfig($event->getConfig());
  }

  /**
   * React to configuration ConfigEvent::RENAME events.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The event to process.
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $this->writeBackConfig($event->getConfig());
  }

  protected function writeBackConfig(Config $config) {
    $config_name = $config->getName();
    $write_back = $this->configFactory->get('config_devel.settings')->get('write_back');;
    $file_names = array_keys(array_intersect($write_back, array($config_name)));
    if ($file_names) {
      $data = $config->get();
      if ($this->configManager->getEntityTypeIdByName($config_name)) {
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
