<?php

namespace Drupal\cebaf_status\Plugin\Block;

/**
 * Provides a 'Contact Info' Block.
 *
 * @Block(
 *   id = "contact_info",
 *   admin_label = @Translation("Contact Info block"),
 *   category = @Translation("CEBAF"),
 * )
 */
class ContactInfoBlock extends \Drupal\Core\Block\BlockBase
{

  /**
   * @inheritDoc
   */
  public function build()
  {
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => 'My List',
      '#items' => ['item 1', 'item 2'],
      '#attributes' => ['class' => 'mylist'],
      '#wrapper_attributes' => ['class' => 'container'],
    ];
  }
}
