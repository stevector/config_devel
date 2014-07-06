<?php

/**
 * @file
 * Contains \Drupal\config_devel\Form\ConfigDevelSettingsForm.
 */

namespace Drupal\config_devel\Form;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Settings form for config devel.
 */
class ConfigDevelSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var array
   */
  protected $keys;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $this->config = $this->configFactory()->get('config_devel.settings');
    $default_value = '';
    foreach ($this->config->get('reinstall') as $file) {
      $default_value .= $file['filename'] . "\n";
    }
    $form['reinstall'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Reinstall'),
      '#default_value' => $default_value,
      '#description' => $this->t('Reinstall these files automatically. List one file per line.'),
    );
    $form['auto_export'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Auto export'),
      '#default_value' => implode("\n", array_keys($this->config->get('auto_export'))),
      '#description' => $this->t('Automatically export to the files specified. List one file per line.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    foreach (array('reinstall', 'auto_export') as $key) {
      $form_state['values'][$key] = array_filter(preg_split("/\r\n/", $form_state['values'][$key]));
    }
    foreach ($form_state['values']['reinstall'] as $file) {
      $name = basename($file, '.' . FileStorage::getFileExtension());
      if (in_array($name, array('system.site', 'core.extension', 'simpletest.settings'))) {
        $this->setFormError($this->t('@name is not compatible with this module', array('@name' => $name)), $form_state);
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $reinstall = array();
    foreach ($form_state['values']['reinstall'] as $file) {
      $reinstall[] = array(
        'filename' => $file,
        'hash' => '',
      );
    }
    $auto_export = array();
    foreach ($form_state['values']['auto_export'] as $file) {
      $auto_export[$file] = basename($file, '.' . FileStorage::getFileExtension());
    }
    $this->config
      ->set('reinstall', $reinstall)
      ->set('auto_export', $auto_export)
      ->save();
    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_devel_settings';
  }

}
