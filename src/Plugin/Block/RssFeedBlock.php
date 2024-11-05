<?php

namespace Drupal\qiita_rss\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an RSS Feed Block.
 *
 * @Block(
 *   id = "rss_feed_block",
 *   admin_label = @Translation("Qiita RSS")
 * )
 */
class RssFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client to fetch RSS feed data.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $config = $this->configFactory->get('qiita_rss.settings');
    $rss_feed_url = $config->get('rss_feed_url');
    $display_count = $config->get('display_count');
    $content = [];
    $counter = 0;

    try {
      $response = $this->httpClient->get($rss_feed_url, [
        'connect_timeout' => 5,
        'timeout' => 5,
      ]);

      if ($response->getStatusCode() !== 200) {
        throw new \Exception('Qiita RSS failed: ' . $response->getReasonPhrase(), $response->getStatusCode());
      }

      $xml = simplexml_load_string($response->getBody()->getContents());
      if (!empty($xml?->entry)) {
        foreach ($xml->entry as $item) {
          if ($counter >= $display_count) {
            break;
          }
          $content[] = [
            'title' => (string) $item->title,
            'url' => (string) $item->url,
          ];
          $counter++;
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('custom_rss_block')->error($e->getMessage());
    }

    return [
      '#theme' => 'item_list',
      '#items' => array_map(function ($item) {
        return [
          '#type' => 'link',
          '#title' => $item['title'],
          '#url' => Url::fromUri($item['url'], ['attributes' => ['target' => '_blank']]),
        ];
      }, $content),
      '#attributes' => ['class' => ['qiita-rss']],
    ];
  }

}
