<?php

namespace SilverStripe\Core\Cache;

use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Cache\Simple\ApcuCache;
use Symfony\Component\Cache\Simple\ChainCache;
use Symfony\Component\Cache\Simple\PhpFilesCache;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

/**
 * Returns the most performant combination of caches available on the system:
 * - `PhpFilesCache` (PHP 7 with opcache enabled)
 * - `ApcuCache` (requires APC) with a `FilesystemCache` fallback (for larger cache volumes)
 * - `FilesystemCache` if none of the above is available
 *
 * Modelled after `Symfony\Component\Cache\Adapter\AbstractAdapter::createSystemCache()`
 */
class DefaultCacheFactory implements CacheFactory
{

    /**
     * @var string Absolute directory path
     */
    protected $directory;

    /**
     * @var string APC version for apcu_add()
     */
    protected $version;

    /**
     * @param string $directory
     * @param string $version
     */
    public function __construct($directory, $version = null)
    {
        $this->directory = $directory;
        $this->version = $version;
    }

    /**
     * @inheritdoc
     */
    public function create($service, array $args = array())
    {
        $namespace = (isset($args['namespace'])) ? $args['namespace'] : '';
        $defaultLifetime = (isset($args['defaultLifetime'])) ? $args['defaultLifetime'] : 0;
        $version = $this->version;
        $directory = $this->directory;

        $apcuSupported = null;
        $phpFilesSupported = null;

        if (null === $apcuSupported) {
            $apcuSupported = ApcuAdapter::isSupported();
        }

        if (!$apcuSupported && null === $phpFilesSupported) {
            $phpFilesSupported = PhpFilesAdapter::isSupported();
        }

        if ($phpFilesSupported) {
            $opcache = Injector::inst()->create(PhpFilesCache::class, false, [$namespace, $defaultLifetime, $directory]);
            return $opcache;
        }

        $fs = Injector::inst()->create(FilesystemCache::class, false, [$namespace, $defaultLifetime, $directory]);
        if (!$apcuSupported) {
            return $fs;
        }
        $apcu = Injector::inst()->create(ApcuCache::class, $namespace, (int) $defaultLifetime / 5, $version);

        return Injector::inst()->create(ChainCache::class, [$apcu, $fs]);
    }
}
