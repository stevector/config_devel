<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelAutoImportSubscriberTest.
 */

namespace Drupal\config_devel\Tests;

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
  public function doAssert(array $data) {
    $this->assertIdentical($data, $this->storage->read(static::CONFIGNAME));
  }


}
