<?php

namespace Drupal\qiita_rss\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config QiitaRss settings for this site.
 */
class QiitaRssConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qiita_rss_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['qiita_rss.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('qiita_rss.settings');
    $form['rss_feed_url'] = [
      '#type' => 'url',
      '#title' => 'RSS Feed URL',
      '#default_value' => $config->get('rss_feed_url'),
      '#description' => '表示するQiita RSSフィードのURLを入力します。',
      '#required' => TRUE,
    ];

    $form['display_count'] = [
      '#type' => 'number',
      '#title' => '表示件数',
      '#default_value' => $config->get('display_count'),
      '#description' => '表示するRSSフィードの件数を入力します。',
      '#required' => TRUE,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $rss_feed_url = $form_state->getValue('rss_feed_url');
    $host = parse_url($rss_feed_url, PHP_URL_HOST);
    if ($host !== 'qiita.com') {
      $form_state->setErrorByName('rss_feed_url', '提供される RSS フィード URL は Qiita ドメイン (qiita.com) のものである必要があります。');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('qiita_rss.settings')
      ->set('rss_feed_url', $form_state->getValue('rss_feed_url'))
      ->set('display_count', $form_state->getValue('display_count'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
