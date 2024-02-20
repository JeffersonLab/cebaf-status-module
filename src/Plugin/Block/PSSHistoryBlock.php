<?php

namespace Drupal\cebaf_status\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Provides a 'Recent Beam Current' Block.
 *
 * @Block(
 *   id = "cebaf_access_pss_history",
 *   admin_label = @Translation("Recent PSS History"),
 *   category = @Translation("CEBAF"),
 * )
 */
class PSSHistoryBlock extends BlockBase {

  /**
   * @inheritDoc
   */
  public function build() {
    return [
      '#title' => 'Recent PSS History',
      '#attributes' => ['class' => ['block-block-content']],
      '#markup' => $this->markup(),
      '#wrapper_attributes' => ['class' => 'container'],
    ];
  }

  /**
   * The url to the current graph
   */
  protected function imgUrl() {
    $baseUrl =  PublicStream::baseUrl() . Drupal::config('cebaf_status.settings')->get('pss_history_path');
    /*
     * In order to limit caching of the graph image file, we are going to
     * append a timestamp-based query parameter.
    */
    $t = ceil(time()/300) * 300; // next 5-minute timestamp
    $url = "{$baseUrl}?t={$t}";
    return $url;
  }


  protected function markup() {
    $markup = '<div class="d-flex flex-column justify-content-center align-items-center">';
    $markup .= "<a href=\"{$this->imgUrl()}\">";
    $markup .= "<img class=sidebar-img alt=\"recent pss history graph\" src=\"{$this->imgUrl()}\" />";
    $markup .= "</a>";
    $markup .= '</div>';
    return $markup;
  }

}
