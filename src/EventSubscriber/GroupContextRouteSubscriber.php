<?php

namespace Drupal\group_purl\EventSubscriber;

use Drupal;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\NullRouteMatch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\purl\Event\ExitedContextEvent;
use Drupal\purl\MatchedModifiers;
use Drupal\purl\PurlEvents;
use Drupal\redirect\Exception\RedirectLoopException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class GroupContextRouteSubscriber.
 */
class GroupContextRouteSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Drupal\purl\MatchedModifiers definition.
   *
   * @var \Drupal\purl\MatchedModifiers
   */
  protected $purlMatchedModifiers;

  /**
   * Constructs a new GroupContextRouteSubscriber object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $current_route_match, MatchedModifiers $purl_matched_modifiers) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
    $this->purlMatchedModifiers = $purl_matched_modifiers;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkGroupContext', -20];

    return $events;
  }

  /**
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   * @param $eventName
   * @param EventDispatcherInterface $eventDispatcher
   */
  public function checkGroupContext(GetResponseEvent $event, $eventName, EventDispatcherInterface $eventDispatcher) {
    $master_route_match = $this->currentRouteMatch->getMasterRouteMatch();
    if (!$master_route_match instanceof NullRouteMatch) {
      $route_options = $master_route_match->getRouteObject()->getOptions();
    }
    else {
      $route_options = $this->currentRouteMatch->getRouteObject()->getOptions();
    }
    $isAdminRoute = array_key_exists('_admin_route', $route_options);
    if (!$master_route_match instanceof NullRouteMatch) {
      $route_name = $master_route_match->getRouteName();
    }
    else {
      $route_name = $this->currentRouteMatch->getRouteName();
    }
    $matched = $this->purlMatchedModifiers->getMatched();
    $url = FALSE;
    $multiple = count($matched) > 1;

    if (empty($matched)) {
      if ($route_name == 'entity.node.canonical' || $route_name == 'entity.node.edit_form' || $route_name == 'entity.node.add') {
        /* @kludge temporary fix for test failure, there's no node in the URL?
         *
         * Argument 1 passed to
         * Drupal\group\Entity\GroupContent::loadByEntity() must implement
         * interface Drupal\Core\Entity\ContentEntityInterface, null given()
         */
        $node = $this->currentRouteMatch->getParameter('node');
        if ($node && $contents = GroupContent::loadByEntity($node)) {
          $group_content = reset($contents);
          $modifier = substr($group_content->getGroup()->path->alias, 1);
          $url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()
            ->all(), [
            'prefix' => $modifier . '/',
          ]);

        }
      }
      elseif ($route_name == 'entity.group.canonical') {

      }
      elseif ($isAdminRoute) {
        return;
      }
    }
    else {
      if ($route_name == 'entity.node.canonical' || $route_name == 'entity.node.edit_form' || $route_name == 'entity.node.add') {
        if ($contents = GroupContent::loadByEntity($this->currentRouteMatch->getParameter('node'))) {
          $group_content = reset($contents);
          $modifier = substr($group_content->getGroup()->path->alias, 1);
          if ($multiple || $modifier != $matched[0]->getModifier()) {
            $url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()
              ->all(), [
                'prefix' => $modifier . '/',
                'purl_exit' => TRUE,
              ]
            );
          }
          // else no redirect needed, success.
        }
        else {
          // this node is not in a group.
          $url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()->all(), [
            'purl_exit' => TRUE,
          ]);
        }
      }
      elseif ($route_name == 'entity.group.canonical') {
        /** @var \Drupal\group\Entity\Group $group */
        $group = $this->currentRouteMatch->getParameter('group');
        $modifier = substr($group->path->alias, 1);

      }
      elseif ($isAdminRoute) {
        // exit group
        //$url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()->all(), [
        //   'purl_exit' => TRUE,
        //  ]);
      }

    }
    //if ($route_name == 'entity.group.canonical') {
    //  if (empty($matched)) {
    //    $matched = 1;
    //  }
    //  if (empty($matched)) {

    //    $url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()
    //      ->all(), [
    //      'host' => $modifier . '.' . Settings::get('purl_base_domain'),
    //      'absolute' => TRUE,
    //      'purl_exit' => TRUE,
    //    ]);
    //  }
    //}
    if ($url) {
      try {
        $redirect_response = new TrustedRedirectResponse($url->toString());
        $redirect_response->getCacheableMetadata()->setCacheMaxAge(0);
        $modifiers = $event->getRequest()->attributes->get('purl.matched_modifiers', []);
        $new_event = new ExitedContextEvent($event->getRequest(), $redirect_response, $this->currentRouteMatch, $modifiers);
        $eventDispatcher->dispatch(PurlEvents::EXITED_CONTEXT, $new_event);
        $event->setResponse($new_event->getResponse());
        return;
      } catch (RedirectLoopException $e) {
        Drupal::logger('redirect')->warning($e->getMessage());
        $response = new Response();
        $response->setStatusCode(503);
        $response->setContent('Service unavailable');
        $event->setResponse($response);
        return;
      }
    }
  }

}
