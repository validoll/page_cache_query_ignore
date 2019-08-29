<?php

namespace Drupal\page_cache_query_ignore\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a ChosenConfig form.
 */
class PageCacheQueryIgnoreConfigForm extends ConfigFormBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_cache_query_ignore_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['page_cache_query_ignore.settings'];
  }

  /**
   * Chosen configuration form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Settings:
    $ignore_conf = $this->configFactory->get('page_cache_query_ignore.settings');

    $form['ignored_parameters'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ignored query parameters'),
      '#required' => TRUE,
      '#default_value' => implode("\n", $ignore_conf->get('ignored_parameters')),
      '#description' => $this->t('List of query parameters to ignore (one parameter per line).'),
      ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Configuration form submit handler.
   *
   * Validates submission by checking for duplicate entries, invalid
   * characters, and that there is an abbreviation and phrase pair.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('page_cache_query_ignore.settings');
    $parameters = array_map('trim', explode("\n", $form_state->getValue('ignored_parameters')));
    $config->set('ignored_parameters', array_filter($parameters));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
