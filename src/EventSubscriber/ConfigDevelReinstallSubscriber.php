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

class ConfigDevelReinstallSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @param ConfigManagerInterface $config_manager
   * @param ConfigFactoryInterface $config_factory
   * @param EntityManagerInterface $entity_manager
   */
  public function __construct(ConfigManagerInterface $config_manager, ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager) {
    $this->configManager = $config_manager;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * Reinstall changed config files.
   */
  public function reinstallConfig() {
    $config = $this->configFactory->get('config_devel.settings');
    $changed = FALSE;
    foreach ($config->get('reinstall') as $key => $file) {
      $contents = file_get_contents($file['filename']);
      $hash = Crypt::hashBase64($contents);
      if ($hash != $file['hash']) {
        $changed = TRUE;
        $config->set("reinstall.$key.hash", $hash);
        $data = Yaml::decode($contents);
        $name = basename($file['filename'], '.yml');
        $entity_type_id = $this->configManager->getEntityTypeIdByName($name);
        if ($entity_type_id) {
          $this->entityManager
            ->getStorage($entity_type_id)
            ->create($data)
            ->save();
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
    $events[KernelEvents::REQUEST][] = array('reinstallConfig', 20);
    return $events;
  }

}
