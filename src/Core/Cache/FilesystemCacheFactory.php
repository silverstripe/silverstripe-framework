<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

class FilesystemCacheFactory implements CacheFactory
{
    /**
     * @var string Absolute directory path
     */
    protected $directory;

    /**
     * @param string $directory
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @inheritdoc
     */
    public function create($service, array $params = [])
    {
        $psr6Cache = Injector::inst()->createWithArgs(FilesystemAdapter::class, [
            (isset($params['namespace'])) ? $params['namespace'] : '',
            (isset($params['defaultLifetime'])) ? $params['defaultLifetime'] : 0,
            $this->directory
        ]);
        return Injector::inst()->createWithArgs(Psr16Cache::class, [$psr6Cache]);
    }
}
