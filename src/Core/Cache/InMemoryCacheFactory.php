<?php

namespace SilverStripe\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Interface to be implemented by all cache factories that instantiate in-memory symfony cache adapters.
 * This allows the cache adapters to be used in conjunction with filesystem cache.
 */
interface InMemoryCacheFactory extends CacheFactory
{
    /**
     * Create a PSR6-compliant cache adapter
     */
    public function createPsr6(string $service, array $params = []): CacheItemPoolInterface;
}
