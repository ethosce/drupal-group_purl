<?php

namespace Drupal\group_purl\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionAccessCheck;
use Symfony\Component\Routing\Route;

/**
 * Access check for a group permission provided by PURL context.
 */
class GroupPurlAccessCheck extends GroupPermissionAccessCheck {

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $context = \Drupal::service('group_purl.context_provider');
    $contexts = $context->getRuntimeContexts(['group']);
    $permission = $route->getRequirement('_group_purl_permission');

    if ($group = $contexts['group']->getContextValue()) {
      return $group->hasPermission($permission, $account) ? AccessResult::allowed() : AccessResult::forbidden();
    }

    return AccessResult::forbidden();
  }

}
