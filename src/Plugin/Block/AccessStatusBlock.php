<?php

namespace Drupal\cebaf_status\Plugin\Block;

use Drupal;
use Drupal\cebaf_status\Plugin\fetcher\ContentFetcher;
use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'Access Status' Block.
 *
 * @Block(
 *   id = "cebaf_access_status",
 *   admin_label = @Translation("Access Status"),
 *   category = @Translation("CEBAF"),
 * )
 */
class AccessStatusBlock extends BlockBase {

  /**
   * Maps the PV names to the prefixes of our gif files
   */
  const  gifPrefixes = [
    'INJ' => 'Inj_',
    'NLC' => 'NL_',
    'SLC' => 'SL_',
    'BSY' => 'BSY_',
    'HLA' => 'A_',
    'HLB' => 'B_',
    'HLC' => 'C_',
    'HLD' => 'D_',
    'TAGV' => 'DTagger_',
  ];

  /**
   * Maps the PV integer values to postfixes of our gif files
   */
  const gifPostfixes = [
    0 => 'unresolved.gif',
    1 => 'restricted.gif',
    2 => 'sweep.gif',
    3 => 'sweep.gif',
    4 => 'controlled.gif',
    5 => 'power.gif',
    6 => 'beam.gif',
    7 => 'beam.gif',
    8 => 'unresolved.gif',    // An FEL-specific laser mode not used at CEBAF
    9 => 'unresolved.gif',    // Lockdown - a PSS error state
    10 => 'restricted.gif',
    11 => 'controlled.gif',
  ];


  protected array $epicsValues = [];

  /**
   * @inheritDoc
   */
  public function build() {
    return [
      '#title' => 'Access Status',
      '#attributes' => ['class' => ['block-block-content']],
      // to match standard block class css
      '#markup' => $this->markup(),
      '#wrapper_attributes' => ['class' => 'container'],
      '#attached' => [
        'library' => ['cebaf_status/access-status'],
        // access-status key is found in cebaf_status.libraries.yml
      ],
    ];
  }

  protected function segments(): array {
    return array_keys(self::gifPrefixes);
  }

  /**
   * The list of PV names for either the A or B chain of the PSS system
   */
  protected function pvNames(): array {
    $pvs = [];
    foreach ($this->segments() as $segment) {
      $pvs[] = "PLC_{$segment}";
    }
    return $pvs;
  }

  protected function getPvs() {
    $baseUrl = Drupal::config('cebaf_status.settings')->get('caget_url');
    $url = $baseUrl . '?n=y&pv=' . implode('&pv=', $this->pvNames());

    $decoded = json_decode($this->data($url));
    return collect($decoded->data)->pluck('value', 'name')->all();
  }

  protected function data($url) {
    $client = \Drupal::service('http_client_factory');
    $logger = \Drupal::service('logger.factory')->get('cebaf');
    $messenger = \Drupal::service('messenger');
    $fetcher = new ContentFetcher($client, $logger, $messenger);
    return $fetcher->fetch($url);
  }

  /**
   * Determine the states of the PSS segments
   */
  protected function segmentStates() {
    $values = [];
    foreach ($this->segments() as $segment) {
      $pvName = "PLC_{$segment}";
        // Since the values matched, we will just work with A chain now
        $state = $this->epicsValues[$pvName];

        if (array_key_exists($state, self::gifPostfixes)) {  // limit to know valid states
          $values[$segment] = $state;
        }
        else {
          $values[$segment] = '0';
      }
    }
    return $values;
  }

  /**
   * The url to a file in this module's accel_state_gifs subdirectory
   */
  protected function gifUrl($filename) {
    global $base_url;   // https://api.drupal.org/api/drupal/core%21globals.api.php/10
    $moduleUrl = $base_url.'/'.\Drupal::moduleHandler()->getModule('cebaf_status')->getPath();
    return $moduleUrl . '/accel_state_gifs/'.$filename;
  }

  protected function markup() {
    $this->epicsValues = $this->getPvs();
    $markup = '<div class="d-flex flex-column justify-content-center align-items-center">';
    $markup .= '<div id="dropshadow">';
    $markup .= sprintf("<img class=\"stacked\" src=%s />",$this->gifUrl('drop_shadow.gif'));
    foreach ($this->segmentStates() as $segment => $state){
      $filename = self::gifPrefixes[$segment] . self::gifPostfixes[$state];
      $markup .= sprintf("<img class=\"stacked\" src=%s />",$this->gifUrl($filename));
    }
    $markup .= '</div>';
    $markup .= '</div>';
    return $markup;
  }

}
