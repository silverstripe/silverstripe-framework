<?php

namespace SilverStripe\Core\Cache;

use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Abstract implementation of CacheFactory which provides methods to easily instantiate PSR6 and PSR16 cache adapters.
 */
abstract class AbstractCacheFactory implements CacheFactory
{
    protected ?LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger Logger instance to assign
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Creates an object with a PSR-16 interface, usually from a PSR-6 class name.
     *
     * Quick explanation of caching standards:
     * - Symfony cache implements the PSR-6 standard
     * - Symfony provides adapters which wrap a PSR-6 backend with a PSR-16 interface
     * - Silverstripe uses the PSR-16 interface to interact with caches. It does not directly interact with the PSR-6 classes
     * - Psr\SimpleCache\CacheInterface is the php interface of the PSR-16 standard. All concrete cache classes Silverstripe code interacts with should implement this interface
     *
     * Further reading:
     * - https://symfony.com/doc/current/components/cache/psr6_psr16_adapters.html#using-a-psr-6-cache-object-as-a-psr-16-cache
     * - https://github.com/php-fig/simple-cache
     */
    protected function createCache(
        string $class,
        array $args,
        bool $useInjector = true
    ): CacheInterface {
        $classIsPsr6 = is_a($class, CacheItemPoolInterface::class, true);
        $classIsPsr16 = is_a($class, CacheInterface::class, true);
        if (!$classIsPsr6 && !$classIsPsr16) {
            throw new InvalidArgumentException("class $class must implement one of " . CacheItemPoolInterface::class . ' or ' . CacheInterface::class);
        }
        $cacheAdapter = $this->instantiateCache($class, $args, $useInjector);
        $psr16Cache = $this->prepareCacheForUse($cacheAdapter, $useInjector);
        return $psr16Cache;
    }

    /**
     * Prepare a cache adapter for use.
     * This wraps a PSR6 adapter inside a PSR16 one. It also adds the loggers.
     */
    protected function prepareCacheForUse(
        CacheItemPoolInterface|CacheInterface $cacheAdapter,
        bool $useInjector
    ): CacheInterface {
        $loggerAdded = false;
        if ($cacheAdapter instanceof CacheItemPoolInterface) {
            $loggerAdded = $this->addLogger($cacheAdapter);
            // Wrap the PSR-6 class inside a class with a PSR-16 interface
            $cacheAdapter = $this->instantiateCache(Psr16Cache::class, [$cacheAdapter], $useInjector);
        }
        if (!$loggerAdded) {
            $this->addLogger($cacheAdapter);
        }
        return $cacheAdapter;
    }

    /**
     * Instantiates a cache adapter, either via the dependency injector or using the new keyword.
     */
    protected function instantiateCache(
        string $class,
        array $args,
        bool $useInjector
    ): CacheItemPoolInterface|CacheInterface {
        if ($useInjector) {
            // Injector is used for in most instances to allow modification of the cache implementations
            return Injector::inst()->createWithArgs($class, $args);
        }
        // ManifestCacheFactory cannot use Injector because config is not available at that point
        return new $class(...$args);
    }

    protected function addLogger(CacheItemPoolInterface|CacheInterface $cache): bool
    {
        if ($this->logger && ($cache instanceof LoggerAwareInterface)) {
            $cache->setLogger($this->logger);
            return true;
        }
        return false;
    }
}
