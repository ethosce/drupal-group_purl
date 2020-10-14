<?php

namespace Drupal\group_purl\Plugin\Purl\Provider;

use Drupal\purl\Plugin\Purl\Provider\ProviderAbstract;
use Drupal\purl\Plugin\Purl\Provider\ProviderInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @PurlProvider(
 *   id = "group_purl_provider",
 *   title = @Translation("A provider pair for Group module.")
 * )
 */
class GroupPurlProvider extends ProviderAbstract implements ProviderInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  protected $storage;

  /**
   * @inheritDoc
   */
  public function getModifierData() {
    $modifiers = [];
    $groups = \Drupal\group\Entity\Group::loadMultiple();

    foreach ($groups as $group) {
      $modifiers[$group->purl->value] = $group->id();
    }

    return $modifiers;
  }

}
