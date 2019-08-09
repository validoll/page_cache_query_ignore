<?php

namespace Drupal\page_cache_query_ignore\StackMiddleware;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\page_cache\StackMiddleware\PageCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Executes the page caching before the main kernel takes over the request.
 *
 * Ignore query parameters also.
 */
class PageCacheIgnore extends PageCache {

  /**
   * A config object for the page cache query parameters ignore.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Ignored parameters.
   *
   * @var array
   */
  protected $ignoredParameters = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(HttpKernelInterface $http_kernel, CacheBackendInterface $cache, RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, ConfigFactoryInterface $config_factory) {
    parent::__construct($http_kernel, $cache, $request_policy, $response_policy);
    $this->config = $config_factory->get('page_cache_query_ignore.settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheId(Request $request) {
    // Once a cache ID is determined for the request, reuse it for the duration
    // of the request. This ensures that when the cache is written, it is only
    // keyed on request data that was available when it was read. For example,
    // the request format might be NULL during cache lookup and then set during
    // routing, in which case we want to key on NULL during writing, since that
    // will be the value during lookups for subsequent requests.
    if (!isset($this->cid)) {
      $cid_parts = [
        $request->getSchemeAndHttpHost() . $this->clear($request->getRequestUri()),
        $request->getRequestFormat(NULL),
      ];
      $this->cid = implode(':', $cid_parts);
    }
    return $this->cid;
  }

  /**
   * Get ignored query params.
   *
   * @return array
   *   Ignored params.
   */
  protected function getIgnoredParams() {
    if (empty($this->ignoredParameters)) {
      $this->ignoredParameters = $this->config->get('ignored_parameters');
    }
    return $this->ignoredParameters;
  }

  /**
   * Clear query string.
   *
   * @param string $value
   *   The value to cleanup.
   *
   * @return string
   *   The cleared value.
   */
  protected function clear($value) {
    $value = preg_replace($this->getRegex(), '', $value);
    $value = str_replace('?&', '?', $value);
    $value = rtrim($value, '?');
    return $value;
  }

  /**
   * Generate regex to clear query string.
   *
   * @return string
   *   The regex string.
   */
  protected function getRegex() {
    $param_for_regex = [];

    foreach ($this->getIgnoredParams() as $param) {
      unset($_REQUEST[$param]);
      unset($_GET[$param]);
      $param_for_regex[] = "(?:[&]{0,1}{$param}=[^&]*)";
    }
    $ignode_trackers_params_regex = implode('|', $param_for_regex);
    $regex = "/{$ignode_trackers_params_regex}/i";
    return $regex;
  }

}
