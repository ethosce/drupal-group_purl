<?php

namespace Drupal\group_purl\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\purl\Event\ModifierMatchedEvent;
use Drupal\purl\PurlEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class GroupContext.
 */
class GroupPurlContext implements ContextProviderInterface, EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * @var  \Drupal\purl\Event\ModifierMatchedEvent
   */
  protected $modifierMatched;

  /**
   * Constructs a new GroupContext object.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param CurrentRouteMatch $currentRouteMatch
   * @param CurrentPathStack $currentPathStack
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $currentRouteMatch) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PurlEvents::MODIFIER_MATCHED] = ['onModifierMatched'];

    return $events;
  }

  /**
   * This method is called whenever the purl.modifier_matched event is
   * dispatched.
   *
   * @param \Drupal\purl\Event\ModifierMatchedEvent $event
   */
  public function onModifierMatched(ModifierMatchedEvent $event) {
    //if ($event->getProvider() != 'group_purl_provider') {
    // We are not interested in modifiers not provided by this module.
    //return;
    //}
    $this->modifierMatched = $event;
    $this->contexts[$event->getMethod()->getId()] = $event->getModifier();
    ksort($this->contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['purl']);

    $group = $this->getGroupFromRoute();
    if ($group) {
      // Create a context from the definition and retrieved or created group.
      $context = EntityContext::fromEntity($group);
      $context->addCacheableDependency($cacheability);

      return ['group' => $context];
    }
    return [];
  }

  /**
   * For context, in addition to an active Purl context, we also want to include
   * the group view page itself.
   *
   * @return null|\Drupal\group\Entity\Group
   *   the active group
   */
  public function getGroupFromRoute() {
    if ($this->modifierMatched !== NULL) {
      $storage = $this->entityTypeManager->getStorage('group');
      $group = $storage->load($this->modifierMatched->getValue());
      return $group;
    }
    return NULL;
    $routename = $this->currentRouteMatch->getRouteName();
    if (strpos($routename, 'entity.group.') === 0) {
      $group = $this->currentRouteMatch->getParameter('group');
      return $group;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('group', $this->t('Group from Purl'));
    return ['group' => $context];
  }

}
