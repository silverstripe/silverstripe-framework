<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Psr16Cache;

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
    public function create($service, array $params = [])
    {
        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);
        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;
        $psr6Cache = Injector::inst()->createWithArgs(ApcuAdapter::class, [
            $namespace,
            $defaultLifetime,
            $this->version
        ]);
        return Injector::inst()->createWithArgs(Psr16Cache::class, [$psr6Cache]);
    }
}
