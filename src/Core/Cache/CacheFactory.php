<?php

namespace SilverStripe\Core\Cache;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Factory as InjectorFactory;

interface CacheFactory extends InjectorFactory
{
    /**
     * Note: While the returned object is used as a singleton (by the originating Injector->get() call),
     * this cache object shouldn't be a singleton itself - it has varying constructor args for the same service name.
     */
    public function create(string $service, array $params = []): CacheInterface;
}
