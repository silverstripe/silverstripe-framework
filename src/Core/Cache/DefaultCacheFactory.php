<?php

namespace SilverStripe\Core\Cache;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

/**
 * Returns the most performant combination of caches available on the system:
 * - `PhpFilesAdapter` (PHP 7 with opcache enabled)
 * - `ApcuAdapter` (requires APC) with a `FilesystemCache` fallback (for larger cache volumes)
 * - `FilesystemAdapter` if none of the above is available
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
    public function create($service, array $args = [])
    {
        // merge args with default
        $args = array_merge($this->args, $args);
        $namespace = isset($args['namespace']) ? $args['namespace'] : '';
        $defaultLifetime = isset($args['defaultLifetime']) ? $args['defaultLifetime'] : 0;
        $directory = isset($args['directory']) ? $args['directory'] : null;
        $version = isset($args['version']) ? $args['version'] : null;

        // In-memory caches are typically more resource constrained (number of items and storage space).
        // Give cache consumers an opt-out if they are expecting to create large caches with long lifetimes.
        $useInMemoryCache = isset($args['useInMemoryCache']) ? $args['useInMemoryCache'] : true;

        // Check support
        $apcuSupported = ($this->isAPCUSupported() && $useInMemoryCache);
        $phpFilesSupported = $this->isPHPFilesSupported();

        // If apcu isn't supported, phpfiles is the next best preference
        if (!$apcuSupported && $phpFilesSupported) {
            return $this->createCache(PhpFilesAdapter::class, [$namespace, $defaultLifetime, $directory]);
        }

        // Create filessytem cache
        $fs = $this->createCache(FilesystemAdapter::class, [$namespace, $defaultLifetime, $directory]);
        if (!$apcuSupported) {
            return $fs;
        }

        // Chain this cache with ApcuCache
        // Note that the cache lifetime will be shorter there by default, to ensure there's enough
        // resources for "hot cache" items in APCu as a resource constrained in memory cache.
        $apcuNamespace = $namespace . ($namespace ? '_' : '') . md5(BASE_PATH);
        $apcu = $this->createCache(ApcuAdapter::class, [$apcuNamespace, (int) $defaultLifetime / 5, $version]);

        return $this->createCache(ChainAdapter::class, [[$apcu, $fs]]);
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
        $obj = Injector::inst()->createWithArgs($class, $args);

        if (is_a($obj, AbstractAdapter::class)) {
            $cache = new SymfonyAdapterToPsr6Cache($obj);
        } else {
            $cache = $obj;
        }

        // Assign cache logger
        if ($this->logger && $cache instanceof LoggerAwareInterface) {
            $cache->setLogger($this->logger);
        }

        return $cache;
    }
}
