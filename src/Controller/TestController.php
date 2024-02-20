<?php

namespace Drupal\cebaf_status\Controller;

use Drupal\cebaf_status\Graph\BeamCurrentGraph;
use Drupal\Core\Controller\ControllerBase;

class TestController extends ControllerBase {
  public function build() {
    // The current_user service, and others, are included by the ControllerBase
    // class. This is helpful to easily access common services, and when you're
    // not worried about unit testing your controller. See the definition of
    // \Drupal\Core\Controller\ControllerBase to learn more about the available
    // services.
    $name = $this->currentUser()->getDisplayName();

    $this->test();

    return [
      '#markup' => $this->t('<p>Welcome @name. This is the content of the journey.example_base route!</p>', ['@name' => $name]),
    ];
  }

  protected function test() {
    $graph = new BeamCurrentGraph();

  }
}
