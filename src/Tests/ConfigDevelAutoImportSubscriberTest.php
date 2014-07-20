<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelAutoImportSubscriberTest.
 */

namespace Drupal\config_devel\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the automated importer.
 *
 * @group config
 */
class ConfigDevelAutoImportSubscriberTest extends KernelTestBase {

  public static $modules = array('config_devel');

  /**
   * Tests a non-entity import
   */
  function testAutoImportSimple() {
    /** @var $storage \Drupal\Core\Config\StorageInterface */
    $storage = $this->container->get('config.storage');
    $config_name = 'config_devel.test';
    $settings['auto_import'][] = array(
      'filename' => "public://$config_name.yml",
      'hash' => '',
    );
    $storage->write('config_devel.settings', $settings);
    $this->assertFalse($storage->exists($config_name));
    $subscriber = $this->container->get('config_devel.auto_import_subscriber');
    for ($i = 2; $i; $i--) {
      $data['test'] = $this->randomString();
      $storage->write('config_devel.test', $data);
      $subscriber->autoImportConfig();
      $this->assertIdentical($data, $storage->read($config_name));
    }
  }

}
