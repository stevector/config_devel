<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelAutoImportSubscriberTestBase.
 */

namespace Drupal\config_devel\Tests;

use Drupal\Component\Serialization\Yaml;
use Drupal\simpletest\KernelTestBase;

abstract class ConfigDevelAutoImportSubscriberTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('config_devel');

  /**
   * Name of the config object.
   */
  const CONFIGNAME = '';

  /**
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * Test the import subscriber.
   */
  public function testAutoImportSubscriber() {
    // Without this the config exporter breaks.
    \Drupal::service('config.installer')->installDefaultConfig('module', 'config_devel');
    /** @var $storage \Drupal\Core\Config\StorageInterface */
    $filename = 'public://'. static::CONFIGNAME . '.yml';
    \Drupal::config('config_devel.settings')
      ->set('auto_import', array(array(
        'filename' => $filename,
        'hash' => '',
      )))
      ->save();
    $this->storage = $this->container->get('config.storage');
    $this->assertFalse($this->storage->exists(static::CONFIGNAME));
    $subscriber = $this->container->get('config_devel.auto_import_subscriber');
    for ($i = 2; $i; $i--) {
      $data['label'] = $this->randomString();
      file_put_contents($filename, Yaml::encode($data));
      $subscriber->autoImportConfig();
      $this->doAssert($data);
    }
  }

  /**
   * Assert that the config import succeeded.
   *
   * @param array $data
   *   The config data as written.
   */
  abstract protected function doAssert(array $data);

}
