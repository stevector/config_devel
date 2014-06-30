<?php

/**
 * Contains \Drupal\config_devel\Config\FileStorageFactory.
 */

namespace Drupal\config_devel\Config;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;

/**
 * Provides a factory for creating file storage objects for config_devel.
 */
class FileStorageFactory {

  /**
   * Creates a new file storage object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object.
   * @param \Drupal\Core\DrupalKernel $kernel
   *   The kernel.
   *
   * @return \Drupal\Core\Config\FileStorage
   */
  public static function create(Settings $settings, DrupalKernel $kernel) {
    $configuration = $settings->get('config_devel', array());
    if (empty($configuration['storage_dir'])) {
      $configuration['storage_dir'] = $settings->get('file_public_path', $kernel->getSitePath() . '/files') . '/config_devel';
    }
    return new FileStorage($configuration['storage_dir']);
  }

}
