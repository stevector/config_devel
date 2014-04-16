<?php

/**
 * Contains \Drupal\config_devel\Config\FileStorageFactory.
 */

namespace Drupal\config_devel\Config;

use Drupal\Core\Config\FileStorage;
use Drupal\Component\Utility\Settings;

/**
 * Provides a factory for creating devel config file storage objects.
 */
class FileStorageFactory {

  /**
   * The settings array.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;

  /**
   * Constructs FileStorageFactory object.
   *
   * @param \Drupal\Component\Utility\Settings $settings
   *   The settings array.
   */
  function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * Returns a FileStorage object working with the active config directory.
   *
   * @return \Drupal\Core\Config\FileStorage FileStorage
   */
  public function get() {
    $config_devel_settings = $this->settings->get('config_devel', array());
    $storage_dir = $this->settings->get('file_public_path', conf_path() . '/files') . '/config_devel';
    if (!empty($config_devel_settings['storage_dir'])) {
      $storage_dir = $config_devel_settings['storage_dir'];
    }
    return new FileStorage($storage_dir);
  }

}
