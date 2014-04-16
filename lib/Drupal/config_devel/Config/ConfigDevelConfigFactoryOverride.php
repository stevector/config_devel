<?php

/**
 * @file
 * Contains \Drupal\config_devel\Config\ConfigDevelConfigFactoryOverride.
 */

namespace Drupal\config_devel\Config;

use Drupal\Component\Utility\Settings;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides write-back functionality from config_devel yaml files.
 */
class ConfigDevelConfigFactoryOverride implements ConfigFactoryOverrideInterface {

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The config_devel configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configDevelStorage;

  /**
   * The config_devel configuration storage.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The settings array.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;

  /**
   * Constructs the ConfigDevelConfigFactoryOverride object.
   *
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage engine.
   * @param \Drupal\Core\Config\StorageInterface $config_devel_storage
   *   The config_devel configuration storage engine.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Component\Utility\Settings $settings
   *   The settings array.
   */
  public function __construct(StorageInterface $active_storage, StorageInterface $config_devel_storage, ConfigFactoryInterface $config_factory, Settings $settings) {
    $this->activeStorage = $active_storage;
    $this->configDevelStorage = $config_devel_storage;
    $this->configFactory = $config_factory;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();
    $config_devel_settings = $this->settings->get('config_devel', array());
    if (empty($config_devel_settings['write_back_to_active'])) {
      return $overrides;
    }

    $config_devel_configs = $this->configDevelStorage->readMultiple($names);
    foreach ($this->activeStorage->readMultiple($names) as $name => $active_config) {
      if (isset($config_devel_configs[$name]) && $config_devel_configs[$name] !== $active_config) {
        $this->activeStorage->write($name, $config_devel_configs[$name]);
        $this->configFactory->reset($name);
        $overrides[$name] = $config_devel_configs[$name];
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return '';
  }

}
