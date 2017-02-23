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
        return Injector::inst()->create(ApcuCache::class, false, [
            (isset($args['namespace'])) ? $args['namespace'] : '',
            (isset($args['defaultLifetime'])) ? $args['defaultLifetime'] : 0,
            $this->version
        ]);
    }
}
