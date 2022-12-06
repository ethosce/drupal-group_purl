<?php

namespace Drupal\group_purl\Plugin\Purl\Method;

use Drupal\purl\Plugin\Purl\Method\PathPrefixMethod;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @PurlMethod(
 *   id="group_prefix",
 *   title = @Translation("Group Content."),
 *   stages = {
 *     Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PROCESS_OUTBOUND
 *   }
 * )
 */
class GroupPrefixMethod extends PathPrefixMethod {

  /**
   *
   */
  public function contains(Request $request, $modifier) {
    $uri = $request->getRequestUri();
    if ($uri === '/' . $modifier) {
      return TRUE;
    }
    return $this->checkPath($modifier, $uri);
  }

  /**
   * @param $modifier
   * @param $uri
   *
   * @return bool
   */
  protected function checkPath($modifier, $uri) {
    if ($uri === '/' . $modifier) {
      return FALSE;
    }
    return strpos($uri, '/' . $modifier . '/') === 0;

  }

  /**
   *
   */
  public function alterRequest(Request $request, $identifier) {
    // cannot use $request->uri as this sets it to the current server URI, making
    // it too late to modify
    $uri = $request->server->get('REQUEST_URI');
    if ($uri === '/' . $identifier) {
      return FALSE;
    }
    // If we try to get the base path from the Request argument, the modifier gets matched twice.
    // getBasePath() indirectly populates the requestUri parameter, which needs to be null before we set the
    // REQUEST_URI parameter.
    $pos = strpos($uri, '/' . $identifier);
    if ($pos !== FALSE) {
      $newPath = substr_replace($uri, '', $pos, strlen($identifier) + 1);
      if (strpos($newPath, '/' . $identifier . '/') === 0) {
        // ... then we have this path multiple times. Redirect up one.
        return new RedirectResponse($newPath);
      }
      if ($newPath === '/') {
        // ... then redirect to the group page.
        return new RedirectResponse('/' . $identifier);
      }
      $request->server->set('REQUEST_URI', $newPath);
      return $request;
    }

    return FALSE;

    $newPath = substr($uri, strlen($identifier) + 1);
    if (empty($newPath)) {
      // we are on the canonical group path...
      $newPath = $uri;
    }
    $request->server->set('REQUEST_URI', $newPath);
    return $request;
  }

  /**
   *
   */
  public function enterContext($modifier, $path, array &$options) {
    if (isset($options['purl_exit']) && $options['purl_exit']) {
      return $path;
    }
    return '/' . $modifier . $path;
  }

  /**
   *
   */
  public function exitContext($modifier, $path, array &$options) {
    if (!$this->checkPath($modifier, $path)) {
      return NULL;
    }

    return substr($path, 0, strlen($modifier) + 1);
  }

}
