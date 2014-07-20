<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelAutoImportSubscriberEntityTest.
 */


namespace Drupal\config_devel\Tests;

/**
 * Tests the automated importer for config entities.
 *
 * @group config
 */
class ConfigDevelAutoImportSubscriberEntityTest extends ConfigDevelAutoImportSubscriberTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('config_test');

  /**
   * {@inheritdoc}
   */
  const CONFIGNAME = 'config_test.dynamic.test';

  /**
   * {@inheritdoc}
   */
  protected function doAssert(array $data) {
    $this->assertIdentical($data['label'], entity_load('config_test', 'test', TRUE)->get('label'));
  }
}
