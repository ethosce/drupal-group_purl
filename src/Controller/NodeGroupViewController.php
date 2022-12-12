<?php

namespace Drupal\group_purl\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group_purl\Context\GroupPurlContext;
use Drupal\node\Controller\NodeViewController;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class NodeGroupViewController.
 */
class NodeGroupViewController extends NodeViewController {

  /**
   * AliasManger definition.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Drupal\group_purl\Context\GroupPurlContext definition.
   *
   * @var \Drupal\group_purl\Context\GroupPurlContext
   */
  protected $groupPurlContextProvider;

  /**
   * Constructs a new NodeGroupViewController object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer, GroupPurlContext $group_purl_context_provider, AliasManagerInterface $aliasManager, AccountInterface $current_user = NULL, EntityRepositoryInterface $entity_repository) {
    parent::__construct($entityTypeManager, $renderer, $current_user, $entity_repository);
    $this->aliasManager = $aliasManager;
    $this->groupPurlContextProvider = $group_purl_context_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('group_purl.context_provider'),
      $container->get('path_alias.manager'),
      $container->get('current_user'),
      $container->get('entity.repository'),
    );
  }

  /**
   * Override view to check group context, and group membership.
   * If this node should appear in a different group context, redirect to it.
   *
   * {@inheritdoc}
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    $group_context = $this->groupPurlContextProvider->getGroupFromRoute();
    $group_contents = GroupContent::loadByEntity($node);
    if ($group_context) {
      // Check to see if we're in a valid group context for this node.
      foreach ($group_contents as $group_content) {
        if ($group_content->getGroup()->id() == $group_context->id()) {
          return parent::view($node, $view_mode, $langcode);
        }
      }
      // no match for this group context. Do we have a group?
      if ($group_content) {
        // Redirect to correct group context.
        $alias = $this->aliasManager->getAliasByPath('/group/' . $group_content->getGroup()->id());
        return new RedirectResponse($alias . $node->toUrl()->toString());
      }
    }
    // Ok. So we're not in a group context, or this node is not in a group.
    foreach ($group_contents as $group_content) {
      if ($group_content->id()) {
        // Redirect to correct group context.
        $alias = $this->aliasManager->getAliasByPath('/group/' . $group_content->getGroup()->id());
        return new RedirectResponse($alias . $node->toUrl()->toString());
        // Redirect into group context.
      }
    }

    return parent::view($node, $view_mode, $langcode);
  }

}
