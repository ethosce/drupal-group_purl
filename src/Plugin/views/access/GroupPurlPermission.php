<?php

namespace Drupal\group_purl\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Plugin\views\access\GroupPermission;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group permission-based access control from PURL
 * context.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "group_purl_permission",
 *   title = @Translation("Group permission from PURL"),
 *   help = @Translation("Access will be granted to users with the specified group permission string.")
 * )
 */
class GroupPurlPermission extends GroupPermission {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $context = \Drupal::service('group_purl.context_provider');
    $contexts = $context->getRuntimeContexts(['group']);

    if ($group = $contexts['group']->getContextValue()) {
      return $group->hasPermission($this->options['group_permission'], $account);
    }
    if ($this->moduleHandler->moduleExists('views_bulk_operations') &&
      in_array($this->routeMatch->getRouteName(), ['views_bulk_operations.confirm', 'views_bulk_operations.execute_configurable', 'views_bulk_operations.update_selection', 'views_bulk_operations.execute_batch'], TRUE)) {
      $data = $this->tempStore->get('views_bulk_operations_' . $this->view->id() . '_' . $this->displayHandler->display['id'])->get($this->currentUser->id());
      if ($data && isset($data['arguments'])) {
        $this->view->setArguments($data['arguments']);
        $arguments = $this->displayHandler->getHandlers('argument');
        $argument_keys = array_keys($arguments);
        foreach ($data['arguments'] as $delta => $value) {
          $argument = $arguments[$argument_keys[$delta]];
          if ($group = $this->entityTypeManager->getStorage('group')->load($value)) {
            $this->group = $group;
            return $this->group->hasPermission($this->options['group_permission'], $account);
          }
        }
      }
    }
    return FALSE;
  }


  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['group_permission']['#description'] =  $this->t('Only users with the selected group permission will be able to access this display.<br /><strong>Warning:</strong> This will only work if there is an activated group PURL. If not, it will always deny access.');
  }

  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_group_purl_permission', $this->options['group_permission']);
  }

}
