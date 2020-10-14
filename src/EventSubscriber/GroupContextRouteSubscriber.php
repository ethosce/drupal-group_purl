<?php

namespace Drupal\group_purl\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\purl\Event\ExitedContextEvent;
use Drupal\purl\PurlEvents;
use Drupal\redirect\Exception\RedirectLoopException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\purl\MatchedModifiers;
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
   */
  public function checkGroupContext(GetResponseEvent $event, $eventName, EventDispatcherInterface $eventDispatcher) {
    $route_options = $this->currentRouteMatch->getRouteObject()->getOptions();
    $isAdminRoute = array_key_exists('_admin_route', $route_options);
    $route = $this->currentRouteMatch->getRouteObject();
    $route_name = $this->currentRouteMatch->getRouteName();
    $matched = $this->purlMatchedModifiers->getMatched();

    if ($isAdminRoute) {
      return;
    }
    $url = FALSE;
    if (preg_match('/entity\.(.*)\.canonical/', $route_name, $match) && $match[1] != 'group') {
      $entity = $this->currentRouteMatch->getParameter($match[1]);
      if (is_a($entity, ContentEntityInterface::class) && $contents = GroupContent::loadByEntity($this->currentRouteMatch->getParameter($match[1]))) {
        $group_content = reset($contents);
        $modifier = $group_content->getGroup()->purl->value;
        if (strpos($modifier, '.') !== FALSE) {
          // domain, has a dot
          $host = $modifier;
        }
        else {
          // path
          $host = Settings::get('purl_base_domain') . '/' . $modifier;
        }
        if (empty($matched) || ($matched[0]->getModifier() != $modifier)) {
          // redirect into group
          $url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()->all(), [
              'host' => $host,
              'absolute' => TRUE,
              'purl_exit' => TRUE,
          ]);
        }
      }
      elseif (!empty($matched)) {
        $url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()->all(), [
            'host' => Settings::get('purl_base_domain'),
            'absolute' => TRUE,
            'purl_exit' => TRUE,
        ]);
      }
    }
    if ($route_name == 'entity.group.canonical') {
      /** @var \Drupal\group\Entity\Group $group */
      $group = $this->currentRouteMatch->getParameter('group');
      $modifier = $group->purl->value;

      if (strpos($modifier, '.') !== FALSE) {
        // domain, has a dot
        $host = $modifier;
      }
      else {
        // path
        $host = Settings::get('purl_base_domain') . '/' . $modifier;
      }

      // if not matched...
      if (empty($matched)) {

        $url = Url::fromRoute($route_name, $this->currentRouteMatch->getRawParameters()
              ->all(), [
            'host' => $host,
            'absolute' => TRUE,
            'purl_exit' => TRUE,
        ]);
      }
    }
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
        \Drupal::logger('redirect')->warning($e->getMessage());
        $response = new Response();
        $response->setStatusCode(503);
        $response->setContent('Service unavailable');
        $event->setResponse($response);
        return;
      }
    }
  }

}
