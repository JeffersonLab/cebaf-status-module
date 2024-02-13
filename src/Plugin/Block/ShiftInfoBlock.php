<?php

namespace Drupal\cebaf_status\Plugin\Block;

use Drupal\cebaf_status\Plugin\fetcher\ContentFetcher;
use Drupal\Core\DependencyInjection\Container;

/**
 * Provides a 'Contact Info' Block.
 *
 * @Block(
 *   id = "shift_info",
 *   admin_label = @Translation("Shift Info block"),
 *   category = @Translation("CEBAF"),
 * )
 */
class ShiftInfoBlock extends \Drupal\Core\Block\BlockBase
{

  /**
   * The base caget url
   */
  const url = 'https://epicswebops.acc.jlab.org/epics2web/caget';

  /**
   * The pvs that hold shift information.
   */
  const pvs = [
    'shift' => 'comm1',
    'crewChief' => 'comm2',
    'operators' => 'comm3',
    'programDeputy' => 'comm5',
    'operationsProgram' => 'comm6',
    'physicsProgram' => 'comm7',
    'announcements' => 'comm8'
  ];


  /**
   * @inheritDoc
   */
  public function build()
  {
    return [
      '#title' => 'Shift Information',
      '#attributes' => ['class' => ['block-block-content']],  // to match standard block class css
      'content' => $this->content(),
      '#wrapper_attributes' => ['class' => 'container'],
    ];
  }

  /**
   *
   * @return string
   */
  protected function url(): string
  {
    return self::url . '?pv=' . implode('&pv=', array_values(self::pvs));
  }

  protected function data()
  {
    $client = \Drupal::service('http_client_factory');
    $logger = \Drupal::service('logger.factory')->get('cebaf');
    $messenger = \Drupal::service('messenger');
    $fetcher = new ContentFetcher($client, $logger, $messenger);
    return $fetcher->fetch($this->url());
  }

  protected function pvValues()
  {
    $decoded = json_decode($this->data());
    return collect($decoded->data)->pluck('value', 'name')->all();
  }

  protected function content()
  {
    return [
      'table' => [
        '#type' => 'table',
        '#attributes' => ['class' => ['table', 'table-striped','table-transparent']],
        '#rows' => $this->contentRenderRows()
      ]
    ];
  }

  /**
   * Shift data as an array of rows in Drupal render array format.
   *
   * @return array
   */
  protected function contentRenderRows()
  {
    $rows = [];
    foreach ($this->contentArray() as $key => $value) {
      $rows[] = [
          ['data' =>  $key, 'header' => TRUE],
          ['data' => $value],
      ];
    }
    return $rows;
  }

  /**
   * An array containing the data with keys as they will be shown on the web.
   * @return array
   */
  protected function contentArray(): array
  {
    $pvValues = $this->pvValues();
    return [
      'Shift' => $pvValues[self::pvs['shift']],
      'CEBAF Program' => $pvValues[self::pvs['physicsProgram']],
      'MCC Crew Chief' => $pvValues[self::pvs['crewChief']],
      'MCC Operators' => $pvValues[self::pvs['operators']],
      'Program Deputy' => $pvValues[self::pvs['programDeputy']],
      'Announcements' => $pvValues[self::pvs['announcements']],
    ];
  }

}

