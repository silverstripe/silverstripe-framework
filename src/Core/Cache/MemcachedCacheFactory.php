<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Simple\MemcachedCache;
use Memcached;

class MemcachedCacheFactory implements CacheFactory
{

    /**
     * @var Memcached
     */
    protected $memcachedClient;

    /**
     * @param Memcached $memcachedClient
     */
    public function __construct(Memcached $memcachedClient)
    {
        $this->memcachedClient = $memcachedClient;
    }

    /**
     * @inheritdoc
     */
    public function create($service, array $params = array())
    {
        return Injector::inst()->create(MemcachedCache::class, false, [
            $this->memcachedClient,
            (isset($args['namespace'])) ? $args['namespace'] : '',
            (isset($args['defaultLifetime'])) ? $args['defaultLifetime'] : 0
        ]);
    }
}
