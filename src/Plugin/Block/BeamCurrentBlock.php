<?php

namespace Drupal\cebaf_status\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Provides a 'Recent Beam Current' Block.
 *
 * @Block(
 *   id = "cebaf_access_recent_beam_current",
 *   admin_label = @Translation("Recent Beam Current"),
 *   category = @Translation("CEBAF"),
 * )
 */
class BeamCurrentBlock extends BlockBase {

  /**
   * @inheritDoc
   */
  public function build() {
    return [
      '#title' => 'Recent Beam Current to Halls',
      '#attributes' => ['class' => ['block-block-content']],
      '#markup' => $this->markup(),
      '#wrapper_attributes' => ['class' => 'container'],
    ];
  }

  /**
   * The url to the current graph
   */
  protected function imgUrl() {
    $baseUrl =  PublicStream::baseUrl() . '/ops/abcd_current.png';
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
    $markup .= "<img class=main-img alt=\"recent beam current graph\" src=\"{$this->imgUrl()}\" />";
    $markup .= "</a>";
    $markup .= '</div>';
    return $markup;
  }

}
