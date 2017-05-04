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
    public function __construct(Memcached $memcachedClient = null)
    {
        $this->memcachedClient = $memcachedClient;
    }

    /**
     * @inheritdoc
     */
    public function create($service, array $params = array())
    {
        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);
        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;
        return Injector::inst()->createWithArgs(MemcachedCache::class, [
            $this->memcachedClient,
            $namespace,
            $defaultLifetime
        ]);
    }
}
