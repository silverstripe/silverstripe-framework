<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Simple\ApcuCache;
use Memcached;

class ApcuCacheFactory implements CacheFactory
{

    /**
     * @var string
     */
    protected $version;

    /**
     * @param string $version
     */
    public function __construct($version = null)
    {
        $this->version = $version;
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
        return Injector::inst()->createWithArgs(ApcuCache::class, [
            $namespace,
            $defaultLifetime,
            $this->version
        ]);
    }
}
