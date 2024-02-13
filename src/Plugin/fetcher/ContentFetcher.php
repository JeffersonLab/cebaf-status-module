<?php

namespace Drupal\cebaf_status\Plugin\fetcher;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a default fetcher implementation.
 *
 * Uses the http_client service to download the feed.
 *
 * @AggregatorFetcher(
 *   id = "aggregator",
 *   title = @Translation("Default fetcher"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler.")
 * )
 */
class ContentFetcher {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * Constructs a ContentFetcher object.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   A Guzzle client object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(ClientFactory $http_client_factory, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->httpClientFactory = $http_client_factory;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client_factory'),
      $container->get('logger.factory')->get('cebaf'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($url) {
    $request = new Request('GET', $url);

    try {
      $response = $this->httpClientFactory->fromOptions(['verify' => FALSE])->send($request);
      $content = (string) $response->getBody();
      return $content;
    }
    catch (TransferException $e) {
      $this->logger->warning('The content from %url seems to be broken because of error "%error".', [
        '%url' => $url,
        '%error' => $e->getMessage(),
      ]);
      $this->messenger->addWarning(new TranslatableMarkup('The content from %url seems to be broken because of error "%error".', [
        '%url' => $url,
        '%error' => $e->getMessage(),
      ]));
      return FALSE;
    }
  }
}
