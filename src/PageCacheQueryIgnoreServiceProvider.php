<?php

namespace Drupal\page_cache_query_ignore;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the services.
 */
class PageCacheQueryIgnoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('http_middleware.page_cache');
    $definition->setClass('Drupal\page_cache_query_ignore\StackMiddleware\PageCacheIgnore')
      ->addArgument(new Reference('config.factory'));
  }

}
