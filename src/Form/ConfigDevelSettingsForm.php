<?php

/**
 * @file
 * Contains \Drupal\config_devel\Form\ConfigDevelSettingsForm.
 */

namespace Drupal\config_devel\Form;

use Drupal\Core\Form\ConfigFormBase;

class ConfigDevelSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

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
      '#title' => t('Reinstall these files automatically. List one file per line'),
      '#default_value' => $default_value,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $form_state['values']['reinstall'] = array_filter(preg_split("/\r\n/", $form_state['values']['reinstall']));
    foreach ($form_state['values']['reinstall'] as $file) {
      $name = basename($file, '.yml');
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
    $this->config->set('reinstall', $reinstall)->save();
    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_devel_settings';
  }

}
