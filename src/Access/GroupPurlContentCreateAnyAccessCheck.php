<?php

namespace Drupal\group_purl\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupContentCreateAnyAccessCheck;
use Drupal\group_purl\Context\GroupPurlContext;
use Symfony\Component\Routing\Route;

/**
 * Class GroupPurlContentCreateAnyAccessCheck.
 *
 * @package Drupal\group_purl\Access
 */
class GroupPurlContentCreateAnyAccessCheck implements AccessInterface {

  /**
   * Group context.
   *
   * @var \Drupal\group_purl\Context\GroupPurlContext
   */
  protected $context;

  /**
   * Access check.
   *
   * @var \Drupal\group\Access\GroupContentCreateAnyAccessCheck
   */
  protected $accessCheck;

  /**
   * GroupPurlContentCreateAnyAccessCheck constructor.
   *
   * @param \Drupal\group_purl\Context\GroupPurlContext $context
   *   Group Context.
   * @param \Drupal\group\Access\GroupContentCreateAnyAccessCheck $accessCheck
   *   Access Check.
   */
  public function __construct(GroupPurlContext $context, GroupContentCreateAnyAccessCheck $accessCheck) {
    $this->context = $context;
    $this->accessCheck = $accessCheck;
  }

  /**
   * Converts the group purl context to a group, and hands off to Group.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultInterface
   *   The result.
   */
  public function access(Route $route, AccountInterface $account) {
    $group = $this->context->getGroupFromRoute();
    if (empty($group)) {
      return AccessResult::forbidden('This is a group route.');
    }
    return $this->accessCheck->access($route, $account, $group);
  }

}
