<?php declare(strict_types = 1);

namespace Drupal\cebaf_status\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PublicStream;

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
    $form['caget_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Epicsweb caget url'),
      '#description' => $this->t('URL for epics2web caget'),
      '#size' => 64,
      '#default_value' => $this->config('cebaf_status.settings')->get('caget_url'),
    ];
    $form['abcd_current_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Recent Beam Current Graph'),
        '#description' => $this->t('File path relative to public:// for Recent Beam Current graphic'),
        '#size' => 64,
        '#default_value' => $this->config('cebaf_status.settings')->get('abcd_current_path'),
    ];
    $form['pss_history_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PSS History Graph'),
      '#description' => $this->t('File path relative to public:// for PSS History graphic'),
      '#size' => 64,
      '#default_value' => $this->config('cebaf_status.settings')->get('pss_history_path'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    // Make sure the paths to graphics exist
    $this->validatePublicPathVariable($form_state->getValue('abcd_current_path'), $form_state);
    $this->validatePublicPathVariable($form_state->getValue('pss_history_path'), $form_state);

    parent::validateForm($form, $form_state);
  }


  protected function validatePublicPathVariable(string $path, FormStateInterface $form_state) {
    $fullPath = PublicStream::basePath() . $path;
    if (! file_exists($fullPath)){
      $message = $this->t("The file {$fullPath} does not exist");
      $form_state->setErrorByName($path,$message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('cebaf_status.settings')
      ->set('caget_url', $form_state->getValue('caget_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
