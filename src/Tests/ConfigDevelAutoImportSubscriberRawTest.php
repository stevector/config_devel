<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelAutoImportSubscriberTest.
 */

namespace Drupal\config_devel\Tests;
use Drupal\Component\Serialization\Yaml;

/**
 * Tests the automated importer for raw config objects.
 *
 * @group config
 */
class ConfigDevelAutoImportSubscriberRawTest extends ConfigDevelAutoImportSubscriberTestBase {

  /**
   * {@inheritdoc}
   */
  const CONFIGNAME = 'config_devel.test';

  /**
   * {@inheritdoc}
   */
  protected function doAssert(array $data, array $exported_data) {
    $this->assertIdentical($data, $this->storage->read(static::CONFIGNAME));
    $this->assertIdentical($data, $exported_data);
  }


}
