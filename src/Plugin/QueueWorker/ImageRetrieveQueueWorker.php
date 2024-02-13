<?php

namespace Drupal\cebaf_status\Plugin\QueueWorker;

use Drupal\cebaf_status\Plugin\fetcher\ContentFetcher;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "cebaf_image_retrieve",
 *   title = @Translation("Image Retrieval Worker"),
 *   cron = {"time" = 60}
 * )
 */
class ImageRetrieveQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The minimum number of seconds to wait before refreshing a retrieved file
   */
  const fetchInterval = 600;

  /**
   * Logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('cebaf'),
    );
  }

  /**
   * @inheritDoc
   */
  public function processItem($data) {
    $this->getFiles();
  }

  protected function needsRefresh($filename) {
    return file_exists($filename) && time() - filemtime($filename) > self::fetchInterval;
  }

  /**
   * Fetches files from remote servers and stores them locally in
   * the drupal public files directory.
   */
  protected function getFiles() {
    // localname => source url
    $filesToGet = [
      'PSS_history.png' => 'http://opsweb.acc.jlab.org/asis/abc_status.php',
      'abcd_current.png' => 'http://opsweb.acc.jlab.org/asis/abcd_current.php',
      'accboard/wb1.jpg' => 'http://accboard.acc.jlab.org/board_images/wb1.jpg',
      'accboard/wb2.jpg' => 'http://accboard.acc.jlab.org/board_images/wb2.jpg',
      'accboard/status_board1.html' => 'http://accboard.acc.jlab.org/board_images/status_board1.html',
      'accboard/status_board2.html' => 'http://accboard.acc.jlab.org/board_images/status_board2.html',
      'workmap.png' => 'http://accweb.acc.jlab.org/puppet-show/screenshot?url=https://ace.jlab.org/workmap/&filename=calendar.png&format=PNG&omitBackground=false&emulateMedia=screen&fullPage=true',
      'presenter.png' => 'http://accweb.acc.jlab.org/puppet-show/screenshot?url=https://ace.jlab.org/presenter/&filename=presenter.png&format=PNG&omitBackground=false&emulateMedia=screen&fullPage=true',
      'calendar.png' => 'http://accweb.acc.jlab.org/puppet-show/screenshot?url=https://ace.jlab.org/calendar/&filename=calendar.png&format=PNG&omitBackground=false&emulateMedia=screen&fullPage=true',
      'shiftplan.png' => 'http://accweb.acc.jlab.org/puppet-show/screenshot?url=https://ace.jlab.org/apps/pd/&filename=shiftplan.png&omitBackground=false&emulateMedia=screen&fullPage=true',
    ];

    // Get the directory where we need to save the files
    $publicPath = \Drupal::service('file_system')->realpath('public://');
    $savePath = $publicPath . '/ops';
    foreach ($filesToGet as $fileName => $url) {
      $filename = "{$savePath}/{$fileName}";

      // Only retrieve a new file if current one is at sufficiently old
      if ($this->needsRefresh($filename)) {
        if ($data = $this->data($url)) {
          if (file_put_contents($filename, $data) === FALSE) {
            $this->logger->error("Unable to write {$filename}");
          }
          else {
            $this->logger->info("Saved {$filename}");
          }
        }
        else {
          $this->logger->error("Unable to fetch {$url}");
        }
      }else{
        $this->logger->info("No need to refresh {$filename} yet");
      }

    }
  }

  protected function data($url) {
    $client = \Drupal::service('http_client_factory');
    $messenger = \Drupal::service('messenger');
    $fetcher = new ContentFetcher($client, $this->logger, $messenger);
    return $fetcher->fetch($url);
  }

}
