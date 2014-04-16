<?php

/**
 * @file
 * Contains Drupal\config_devel\EventSubscriber\ConfigDevelFileStorageSubscriber.
 */

namespace Drupal\config_devel\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ConfigCrudEvent;
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
   * Constructs the ConfigDevelFileStorageSubscriber object.
   *
   * @param \Drupal\Core\Config\FileStorage $filestorage
   */
  function __construct(FileStorage $filestorage) {
    $this->filestorage = $filestorage;
  }

  /**
   * React to configuration ConfigEvent::SAVE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $this->filestorage->write($config->getName(), $config->get());
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
