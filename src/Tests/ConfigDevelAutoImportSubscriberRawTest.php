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

  const CONFIGNAME = 'config_devel.test';

  /**
   * Tests a non-entity import
   */
  public function doAssert(array $data) {
    $this->assertIdentical($data, $this->storage->read(static::CONFIGNAME));
  }


}
