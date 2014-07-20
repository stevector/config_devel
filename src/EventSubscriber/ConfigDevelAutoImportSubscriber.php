<?php

/**
 * @file
 * Contains \Drupal\config_devel\EventSubscriber\ConfigDevelReinstallSubscriber.
 */

namespace Drupal\config_devel\EventSubscriber;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ConfigDevelAutoImportSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @param ConfigManagerInterface $config_manager
   * @param ConfigFactoryInterface $config_factory
   * @param EntityManagerInterface $entity_manager
   */
  public function __construct(ConfigManagerInterface $config_manager, ConfigFactoryInterface $config_factory) {
    $this->configManager = $config_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Reinstall changed config files.
   */
  public function autoImportConfig() {
    $config = $this->configFactory->get('config_devel.settings');
    $changed = FALSE;
    foreach ($config->get('auto_import') as $key => $file) {
      $contents = @file_get_contents($file['filename']);
      if (!$contents) {
        continue;
      }
      $hash = Crypt::hashBase64($contents);
      if ($hash != $file['hash']) {
        $changed = TRUE;
        $config->set("auto_import.$key.hash", $hash);
        $data = Yaml::decode($contents);
        $name = basename($file['filename'], '.yml');
        $entity_type_id = $this->configManager->getEntityTypeIdByName($name);
        if ($entity_type_id) {
          $entity_manager = $this->configManager->getEntityManager();
          /** @var $entity_storage \Drupal\Core\Config\Entity\ConfigEntityStorageInterface */
          $entity_storage = $entity_manager->getStorage($entity_type_id);
          // getIDFromConfigName adds a dot but getConfigPrefix has a dot
          // already.
          $entity_id = $entity_storage::getIDFromConfigName($name, substr($entity_storage->getConfigPrefix(), 0, -1));
          $entity_type = $entity_manager->getDefinition($entity_type_id);
          $id_key = $entity_type->getKey('id');
          $data[$id_key] = $entity_id;
          $entity = $entity_storage->create($data);
          if ($existing_entity = $entity_storage->load($entity_id)) {
            $entity
              ->set($entity_type->getKey('uuid'), $existing_entity->uuid())
              ->enforceIsNew(FALSE);
          }
          $entity_storage->save($entity);
        }
        else {
          $this->configFactory->get($name)->setData($data)->save();
        }
      }
    }
    if ($changed) {
      $config->save();
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('autoImportConfig', 20);
    return $events;
  }

}
