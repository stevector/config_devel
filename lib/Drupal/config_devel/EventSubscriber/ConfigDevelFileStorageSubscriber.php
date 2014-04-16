<?php

/**
 * @file
 * Contains Drupal\config_devel\EventSubscriber\ConfigDevelFileStorageSubscriber.
 */

namespace Drupal\config_devel\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Component\Utility\Settings;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigEvents;

/**
 * ConfigDevelFileStorageSubscriber subscriber for configuration CRUD events.
 */
class ConfigDevelFileStorageSubscriber implements EventSubscriberInterface {

  /**
   * The configuration file storage object.
   *
   * @var \Drupal\Core\Config\FileStorage
   */
  protected $filestorage;

  /**
   * The default extension file storage.
   *
   * @var \Drupal\Core\Config\InstallStorage
   */
  protected $defaultStorage;

  /**
   * The settings array.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;

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
   * @param \Drupal\Core\Config\StorageInterface $filestorage
   *   The config_devel configuration storage engine.
   * @param \Drupal\Core\Config\InstallStorage $default_storage
   *   The default configuration storage engine.
   * @param \Drupal\Component\Utility\Settings $settings
   *   The settings array.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(FileStorage $filestorage, InstallStorage $default_storage, Settings $settings, ConfigManagerInterface $config_manager) {
    $this->filestorage = $filestorage;
    $this->defaultStorage = $default_storage;
    $this->settings = $settings;
    $this->configManager = $config_manager;
    $this->initialize();
  }

  protected function initialize() {
    $this->defaultConfigToWriteBack = array();
    $config_devel_settings = $this->settings->get('config_devel', array());
    if (!empty($config_devel_settings['write_back_to_default_config'])) {
      $this->defaultConfigToWriteBack = $this->defaultStorage->getComponentNames('module', $config_devel_settings['write_back_to_default_config']);
    }
  }

  /**
   * React to configuration ConfigEvent::SAVE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    $this->filestorage->write($name, $config->get());
    if (isset($this->defaultConfigToWriteBack[$name])) {
      $this->writeBackToDefaultConfig($config, $this->defaultConfigToWriteBack[$name]);
    }
  }

  protected function writeBackToDefaultConfig(Config $config, $directory) {
    $target = $directory . '/' . $config->getName() . '.' . FileStorage::getFileExtension();
    if (file_exists($target) && is_writable($target)) {
      $data = $config->get();
      if ($this->configManager->getEntityTypeIdByName($config->getName())) {
        unset($data['uuid']);
      }
      try {
        file_put_contents($target, $this->filestorage->encode($data));
      }
      catch(DumpException $e) { }
    }
  }

  /**
   * React to configuration ConfigEvent::RENAME events.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The event to process.
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $config = $event->getConfig();
    $this->filestorage->write($config->getName(), $config->get());
    $this->filestorage->delete($event->getOldName());
  }

  /**
   * React to configuration ConfigEvent::DELETE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $this->filestorage->delete($event->getConfig()->getName());
  }

  /**
   * React to configuration ConfigEvent::IMPORT events.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The event to process.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
  }

  /**
   * React to Kernel::REQUEST event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    \Drupal::configFactory()->setOverrideState(TRUE);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest');
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 10);
    $events[ConfigEvents::DELETE][] = array('onConfigDelete', 10);
    $events[ConfigEvents::RENAME][] = array('onConfigRename', 10);
    $events[ConfigEvents::IMPORT][] = array('onConfigImport', 10);
    return $events;
  }

}
