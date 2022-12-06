<?php

namespace Drupal\group_purl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group_purl\Context\GroupPurlContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class GroupPurlContentController.
 */
class GroupPurlContentController extends ControllerBase {

  /**
   * Group Purl Context.
   *
   * @var \Drupal\group_purl\Context\GroupPurlContext
   */
  protected $context;

  /**
   * Group Content Controller.
   *
   * @var \Drupal\group\Entity\Controller\GroupContentController
   */
  protected $groupContentController;

  /**
   * GroupPurlContentController constructor.
   *
   * @param \Drupal\group_purl\Context\GroupPurlContext $context
   *   GroupPurlContext service.
   * @param \Drupal\group\Entity\Controller\GroupContentController $controller
   *   GroupContentController instance.
   */
  public function __construct(
    GroupPurlContext $context,
    GroupContentController $controller) {
    $this->context = $context;
    $this->groupContentController = $controller;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_purl.context_provider'),
      GroupContentController::create($container)
    );
  }

  /**
   * Provides a group content creation form.
   *
   * @param string $plugin_id
   *   The group content enabler to add content with.
   *
   * @return array
   *   A group content creation form.
   */
  public function createForm($plugin_id) {
    $group = $this->context->getGroupFromRoute();
    if (empty($group)) {
      throw new NotFoundHttpException('No group found.');
    }
    return $this->groupContentController->createForm($group, $plugin_id);
  }

  /**
   * Title callback.
   *
   * @param string $plugin_id
   *   The group content enabler to create content with.
   *
   * @return string
   *   The page title.
   */
  public function createFormTitle($plugin_id) {
    $group = $this->context->getGroupFromRoute();
    if (empty($group)) {
      throw new NotFoundHttpException('No group found.');
    }
    return $this->groupContentController->createFormTitle($group, $plugin_id);
  }

}
