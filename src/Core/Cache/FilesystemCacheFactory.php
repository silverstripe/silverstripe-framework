<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Simple\FilesystemCache;

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
    public function create($service, array $params = array())
    {
        return Injector::inst()->create(FilesystemCache::class, false, [
            (isset($args['namespace'])) ? $args['namespace'] : '',
            (isset($args['defaultLifetime'])) ? $args['defaultLifetime'] : 0,
            $this->directory
        ]);
    }
}
