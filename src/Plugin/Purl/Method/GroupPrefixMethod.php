<?php

namespace Drupal\group_purl\Plugin\Purl\Method;

use Drupal\purl\Plugin\Purl\Method\PathPrefixMethod;
use Symfony\Component\HttpFoundation\Request;

/**
 * @PurlMethod(
 *   id="group_prefix",
 *   title = @Translation("Group Content."),
 *   stages = {
 *      Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PROCESS_OUTBOUND,
 *      Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PRE_GENERATE
 *   }
 * )
 */
class GroupPrefixMethod extends PathPrefixMethod {

  /**
   * {@inheritdoc}
   */
  public function contains(Request $request, $modifier) {
    $uri = $request->getPathInfo();
    if ($uri === '/' . $modifier) {
      return FALSE;
    }
    return $this->checkPath($modifier, $uri);
  }

  protected function checkPath($modifier, $uri) {
    if ($uri === '/' . $modifier) {
      return FALSE;
    }
    return strpos($uri, '/' . $modifier . '/') === 0;

  }

  /**
   * {@inheritdoc}
   */
  public function alterRequest(Request $request, $identifier) {
    // cannot use $request->uri as this sets it to the current server URI, making
    // it too late to modify
    $uri = $request->server->get('REQUEST_URI');
    // If we try to get the base path from the Request argument, the modifier gets matched twice.
    // getBasePath() indirectly populates the requestUri parameter, which needs to be null before we set the
    // REQUEST_URI parameter.
    $basePath = \Drupal::request()->getBasePath();
    $newPath = substr_replace($uri, $basePath, 0, (\strlen($identifier) + 1));
    if ($newPath == '/') {
      // Request for the group frontpage.
      // Note: we can change $newPath if we wanted to set a custom group homepage.
      $newPath = '/' . $identifier;
    }
    $request->server->set('REQUEST_URI', $newPath);

    return $request;
  }

  /**
   * {@inheritdoc}
   */
  public function enterContext($modifier, $path, array &$options) {
    if (isset($options['purl_exit']) && $options['purl_exit']) {
      return $path;
    }
    return '/' . $modifier . $path;
  }

  /**
   * {@inheritdoc}
   */
  public function exitContext($modifier, $path, array &$options) {
    if (!$this->checkPath($modifier, $path)) {
      return NULL;
    }

    return substr($path, 0, strlen($modifier) + 1);
  }

  /**
   * {@inheritdoc}
   */
  public function preGenerateEnter() {

  }

}
