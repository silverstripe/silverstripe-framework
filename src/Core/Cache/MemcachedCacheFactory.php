<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Psr16Cache;
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
    public function create($service, array $params = [])
    {
        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);
        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;
        $psr6Cache = Injector::inst()->createWithArgs(MemcachedAdapter::class, [
            $this->memcachedClient,
            $namespace,
            $defaultLifetime
        ]);
        return Injector::inst()->createWithArgs(Psr16Cache::class, [$psr6Cache]);
    }
}
