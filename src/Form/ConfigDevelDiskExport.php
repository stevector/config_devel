<?php

namespace Drupal\config_devel\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Component\Serialization\Yaml;


/**
 * Class ConfigDevelDiskExport.
 */
class ConfigDevelDiskExport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.manager')
    );
  }

  /**
   * Constructs a ConfigFileSystemExporter object.
   */
  public function __construct(ConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_devel_disk_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo, verify that the config directory is writable.
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Export to disk'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo, ideally this method would call a service to do the actual writing.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Delete all yml files before they are replaced. This behavior is one
    // that drush does but DrupalConsole does not.
    $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    $existing_config_files = array_keys(file_scan_directory($directory, '/.*\.yml$/'));
    foreach($existing_config_files as $existing_config_file) {
      file_unmanaged_delete($existing_config_file);
    }

    // This code is from DrupalConsole's ExportCommand.php
    // https://github.com/hechoendrupal/DrupalConsole/blob/master/src/Command/Config/ExportCommand.php
    $configManager = $this->configManager;
    // Get raw configuration data without overrides.
    foreach ($configManager->getConfigFactory()->listAll() as $name) {
      $configData = $configManager->getConfigFactory()
        ->get($name)
        ->getRawData();
      $configName = sprintf('%s.yml', $name);
      $ymlData = Yaml::encode($configData);
      $configFileName = sprintf('%s/%s', $directory, $configName);
      file_put_contents($configFileName, $ymlData);
      // @todo report on the files being written.
    }
    if (is_writable($directory)) {
      drupal_set_message('Your site configuration has been written to the filesystem.');
    }
    else {
      drupal_set_message('Your site configuration cannot be written to the filesystem because it is not writeable.', 'error');
    }
  }
}