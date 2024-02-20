<?php

namespace Drupal\cebaf_status\Utility;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;

class MySampler {
  /*
   * The URL for mysampler data
   */
  protected string $url;

  /*
   * Beginning date of interval to be sampled
   */
  protected Carbon $begin;

  /*
   * Interval between samples in seconds
   */
  protected int $stepSize;

  /*
   * Query parameters passed to mysampler endpoint
   * @see https://github.com/JeffersonLab/myquery/wiki/API-Reference#mysampler
   */
  protected array $query;

  /**
   * Logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /*
   * Data organized into an array formatted as
   * [
   *   'date1' => [
   *      pv1 => value,
   *      pv2 => value,
   *   ]
   *  'date2' => [
   *     pv1 => value,
   *     pv2 => value,
   *   ]
   * ]
   */
  public array $organizedData = [];
  /*
       * Errors encountered during a partially successful fetch will be recorded as warning messages.
       */
  public array $warnings = [];

  /**
   * MySamplerData constructor.
   *
   * If the number of steps to retrieve is not specified, it will be calculated
   * by figuring out how many samples of stepSize will fill the interval between begin
   * and now().
   *
   * Note that stepSize should be specified here in seconds.
   */
  public function __construct(string $begin, mixed $channels, int $stepSize = null, int $numSteps = null)
  {
    $this->url = 'https://epicsweb.jlab.org/myquery/mysampler';
    $this->begin = new Carbon($begin);
    $this->stepSize = $stepSize ?: 120;
    $this->query = [
      'c' => (is_array($channels) ? implode(',', $channels) : $channels),
      's' => $this->stepSizeinMilliSeconds(),
      'n' => $this->numSteps($numSteps),
      'b' => $this->begin->format('Y-m-d') . 'T' . $this->begin->format('H:i'),  // ISO8601 format
      'm' => 'ops',
      'x' => 's',
    ];
    dpm($this->numSteps($numSteps));
    $this->logger = \Drupal::service('logger.factory')->get('mysampler');
  }

  function stepSizeinMilliSeconds()
  {
    return $this->stepSize * 1000;
  }

  /**
   * Use stepSize to compute the number of steps to bridge
   * the time span between the specified begins date and now.
   */
  public function calcNumSteps(): int
  {
    $seconds = Carbon::now()->diffInSeconds($this->begin);
    dpm($seconds);
    return (int)floor(abs($seconds) / $this->stepSize);
  }

  /**
   * Choose the smaller value between calculated number of steps
   * and the max allowed number of steps.
   * The calculated value prevents requesting steps in the future.
   * The max value prevents asking for too many data points from the server.
   */
  public function numSteps($numSteps = null): int
  {
    $desired = $numSteps ?: $this->calcNumSteps();
    $max = 5000;
    return ($desired && $desired > $max) ? $max : $desired;
  }

  protected function organize(array $channelData): array
  {
    $this->organizedData = [];
    if (! array_key_exists('channels', $channelData)){
      $this->logger->error('No channels');
      $this->logger->info(json_encode($channelData));
    }else{
      foreach ($channelData['channels'] as $channel => $response) {
        if (array_key_exists('data', $response)) {
          foreach ($response['data'] as $data) {
            if (!array_key_exists('v', $data) || stristr($data['v'], 'undefined')) {
              $this->organizedData[$data['d']][$channel] = null;
            } else {
              $this->organizedData[$data['d']][$channel] = $data['v'];
            }
          }
        }
        if (array_key_exists('error', $response)) {
          $this->warnings[] = $response['error'];
        }
      }
    }
    return $this->organizedData;
  }

  /**
   *
   * Return with
   *
   */
  public function compatibleData()
  {
    $data = [];
    foreach ($this->organizedData as $date => $values) {
      $data[] = ['date' => $date] + $values;
    }
    return $data;
  }

  public function hasWarnings()
  {
    return !empty($this->warnings);
  }

  /**
   */
  public function getData()
  {
    $httpClient = \Drupal::httpClient();
    $response = $httpClient->get($this->url, ['query' => $this->query]);
    if ($response->getStatusCode() == 200){
      //dpm($response->getBody()->getContents());
      return $this->organize(json_decode($response->getBody()->getContents(), TRUE));
//      return collect($this->compatibleData());
    }

//    // Success response is easy, just return properly reorganized json response.
//    if ($response->successful()) {
//      $this->organize($response->json());
//      return collect($this->compatibleData());
//    }
//    // A 400 client error will ideally be a json response and might even have partial
//    // response data along with error messages.  Or the body might be HTML if a proxy clobbered the json response.
//    // The proper course of action will therefore depend on the content-type returned.
//    if ($response->clientError()) {
//      if (stristr($response->header('content-type'), 'application/json')) {
//        // The organize function will extract partial results and populate warnings
//        $this->organize($response->json());
//        foreach ($this->warnings as $warning) {
//          $this->logger->warning($warning);
//        }
//        throw new WebClientException('The server responded with error messages. See log file.');
//      } else {
//        $this->logger->error((string)Http::get($this->url, $this->query)->effectiveUri());
//        $this->logger->error($response->body());
//        throw new WebClientException('The request could not be processed.');
//      }
//    }
//    if ($response->serverError()) {
//      $this->logger->error((string)Http::get($this->url, $this->query)->effectiveUri());
//      $this->logger->error($response->body());
//      throw new WebServerException('The server responded with an error. See log file.');
//    }
//    throw new \Exception('An unexpected error occurred getting archiver data. See log file.');
  }
}
