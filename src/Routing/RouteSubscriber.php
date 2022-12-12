<?php

namespace Drupal\group_purl\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add a wrapper around entity.node.canonical to redirect group nodes
    // if not in active context.
    if ($route = $collection->get('entity.node.canonical')) {
      $route->setOption('_orig_controller', $route->getDefault('_controller'));
      $route->setDefault('_controller', '\Drupal\group_purl\Controller\NodeGroupViewController:view');
    }
    // Change the path for the entity.group.canonical route
    //if ($route = $collection->get('entity.group.canonical')) {
    //  $route->setPath('/group/{group}/view');
    //}
  }

}
