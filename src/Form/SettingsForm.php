<?php declare(strict_types = 1);

namespace Drupal\cebaf_status\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure CEBAF Status Module settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cebaf_status_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['cebaf_status.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['ca_get_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Epicsweb caget url'),
      '#description' => $this->t('URL for epics2web caget'),
      '#size' => 64,
      '#default_value' => $this->config('cebaf_status.settings')->get('ca_get_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if ($form_state->getValue('example') === 'wrong') {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('The value is not correct.'),
    //     );
    //   }
    // @endcode
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('cebaf_status.settings')
      ->set('ca_get_url', $form_state->getValue('ca_get_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
