<?php

namespace Drupal\cebaf_status\Plugin\Block;

use Drupal;
use Drupal\cebaf_status\Plugin\fetcher\ContentFetcher;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StreamWrapper\PublicStream;

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
   * The base caget url
   * @TODO store in a module config setting
   */
  const url = 'https://epicswebops.acc.jlab.org/epics2web/caget';

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
   * Maps the PV values to postfixes of our gif files
   */
  const gifPostfixes = [
    "Beam Permit" => 'beam.gif',
    "Power Permit" => 'power.gif',
    "Controlled" => 'controlled.gif',
    "Sweep" => 'sweep.gif',
    "Sweep Progress" => 'sweep.gif',
    "Sweep Complete" => 'sweep.gif',
    "Restricted" => 'restricted.gif',
    "Unresolved" => 'unresolved.gif',
  ];

  protected array $chainAValues = [];

  protected array $chainBValues = [];

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
  protected function pvNamesForChain(string $chain): array {
    $pvs = [];
    foreach ($this->segments() as $segment) {
      $pvs[] = "PLC_{$segment}_{$chain}";
    }
    return $pvs;
  }

  protected function getPvsForChain($chain) {
    $url = self::url . '?pv=' . implode('&pv=', $this->pvNamesForChain($chain));
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
      $aKey = "PLC_{$segment}_A";
      $bKey = "PLC_{$segment}_B";

      // If the two chains mismatch, the segments should show unresolved
      if ($this->chainAValues[$aKey] !== $this->chainBValues[$bKey]) {
        $values[$segment] = 'Unresolved';
      }
      else {
        // Since the values matched, we will just work with A chain now
        $state = $this->chainAValues[$aKey];
        //Beam Permit shows up as "Beam Permit 1" or "Beam Permit 2" Get rid of
        //the 1 and 2 to make the state.
        if (substr($state, 0, 4) == 'Beam') {
          $values[$segment] = 'Beam Permit';
        }
        elseif (array_key_exists($state, self::gifPostfixes)) {  // limit to know valid states
          $values[$segment] = $state;
        }
        else {
          $values[$segment] = 'Unresolved';
        }
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
    $this->chainAValues = $this->getPvsForChain('A');
    $this->chainBValues = $this->getPvsForChain('B');
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
