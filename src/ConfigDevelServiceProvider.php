<?php

/**
 * @file
 * Contains \Drupal\config_devel\ConfigDevelServiceProvider.
 */


namespace Drupal\config_devel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Take over config storage.
 */
class ConfigDevelServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /** @var \Drupal\Core\Config\FileStorage $destination */
    $destination = $container->get('config_devel.filestorage');
    if (!$destination->exists('core.extension')) {
      $source = $container->get('config.storage');
      foreach ($source->listAll() as $name) {
        $destination->write($name, $source->read($name));
      }
    }
    $container->set('config.storage', $destination);
    $container->removeDefinition('config.storage');
    $container->setAlias('config.storage', 'config_devel.filestorage');
  }

}

