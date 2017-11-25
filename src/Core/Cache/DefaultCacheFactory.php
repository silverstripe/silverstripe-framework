<?php

namespace SilverStripe\Core\Cache;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
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
    protected $args = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param array $args List of global options to merge with args during create()
     * @param LoggerInterface $logger Logger instance to assign
     */
    public function __construct($args = [], LoggerInterface $logger = null)
    {
        $this->args = $args;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function create($service, array $args = array())
    {
        // merge args with default
        $args = array_merge($this->args, $args);
        $namespace = isset($args['namespace']) ? $args['namespace'] : '';
        $defaultLifetime = isset($args['defaultLifetime']) ? $args['defaultLifetime'] : 0;
        $directory = isset($args['directory']) ? $args['directory'] : null;
        $version = isset($args['version']) ? $args['version'] : null;

        // Check support
        $apcuSupported = $this->isAPCUSupported();
        $phpFilesSupported = $this->isPHPFilesSupported();

        // If apcu isn't supported, phpfiles is the next best preference
        if (!$apcuSupported && $phpFilesSupported) {
            return $this->createCache(PhpFilesCache::class, [$namespace, $defaultLifetime, $directory]);
        }

        // Create filessytem cache
        $fs = $this->createCache(FilesystemCache::class, [$namespace, $defaultLifetime, $directory]);
        if (!$apcuSupported) {
            return $fs;
        }

        // Chain this cache with ApcuCache
        $apcuNamespace = $namespace . ($namespace ? '_' : '') . md5(BASE_PATH);
        $apcu = $this->createCache(ApcuCache::class, [$apcuNamespace, (int) $defaultLifetime / 5, $version]);
        return $this->createCache(ChainCache::class, [[$apcu, $fs]]);
    }

    /**
     * Determine if apcu is supported
     *
     * @return bool
     */
    protected function isAPCUSupported()
    {
        static $apcuSupported = null;
        if (null === $apcuSupported) {
            // Need to check for CLI because Symfony won't: https://github.com/symfony/symfony/pull/25080
            $apcuSupported = Director::is_cli() ? ini_get('apc.enable_cli') && ApcuAdapter::isSupported() : ApcuAdapter::isSupported();
        }
        return $apcuSupported;
    }

    /**
     * Determine if PHP files is supported
     *
     * @return bool
     */
    protected function isPHPFilesSupported()
    {
        static $phpFilesSupported = null;
        if (null === $phpFilesSupported) {
            $phpFilesSupported = PhpFilesAdapter::isSupported();
        }
        return $phpFilesSupported;
    }

    /**
     * @param string $class
     * @param array $args
     * @return CacheInterface
     */
    protected function createCache($class, $args)
    {
        /** @var CacheInterface $cache */
        $cache = Injector::inst()->createWithArgs($class, $args);

        // Assign cache logger
        if ($this->logger && $cache instanceof LoggerAwareInterface) {
            $cache->setLogger($this->logger);
        }

        return $cache;
    }
}
