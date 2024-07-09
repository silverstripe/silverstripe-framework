<?php

namespace SilverStripe\Core\Cache;

use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

/**
 * Creates the following cache adapters:
 * - `PhpFilesAdapter` (falls back to `FilesystemAdapter` if `PhpFilesAdapter` isn't supported)
 * - An optional in-memory cache such as Redis, Memcached, or APCu.
 */
class DefaultCacheFactory extends AbstractCacheFactory
{
    protected array $args = [];

    /**
     * @param array $args List of global options to merge with args during create()
     * @param LoggerInterface $logger Logger instance to assign
     */
    public function __construct($args = [], LoggerInterface $logger = null)
    {
        $this->args = $args;
        parent::__construct($logger);
    }

    /**
     * @inheritdoc
     */
    public function create(string $service, array $args = []): CacheInterface
    {
        // merge args with default
        $args = array_merge($this->args, $args);
        $namespace = isset($args['namespace']) ? $args['namespace'] : '';
        $defaultLifetime = (int) (isset($args['defaultLifetime']) ? $args['defaultLifetime'] : 0);
        $directory = isset($args['directory']) ? $args['directory'] : null;
        $useInjector = isset($args['useInjector']) ? $args['useInjector'] : true;

        // In-memory caches are typically more resource constrained (number of items and storage space).
        // Give cache consumers an opt-out if they are expecting to create large caches with long lifetimes.
        $useInMemoryCache = isset($args['useInMemoryCache']) ? $args['useInMemoryCache'] : true;
        $inMemoryCacheFactory = Environment::getEnv('SS_IN_MEMORY_CACHE_FACTORY');

        $filesystemCache = $this->instantiateFilesystemCache($namespace, $defaultLifetime, $directory, $useInjector);
        if (!$useInMemoryCache || !$inMemoryCacheFactory) {
            return $this->prepareCacheForUse($filesystemCache, $useInjector);
        }

        // Check if SS_IN_MEMORY_CACHE_FACTORY is a factory
        if (!is_a($inMemoryCacheFactory, InMemoryCacheFactory::class, true)) {
            throw new LogicException(
                'The value in your SS_IN_MEMORY_CACHE_FACTORY environment variable'
                . ' is not a valid InMemoryCacheFactory class name'
            );
        }

        // Note that the cache lifetime will be shorter there by default, to ensure there's enough
        // resources for "hot cache" items as a resource constrained in memory cache.
        $inMemoryLifetime = (int) ($defaultLifetime / 5);
        $inMemoryCache = $this->instantiateInMemoryCache(
            $service,
            $inMemoryCacheFactory,
            ['namespace' => $namespace, 'defaultLifetime' => $inMemoryLifetime, 'useInjector' => $useInjector],
            $useInjector
        );

        // The ChainAdapter doesn't take a logger, so we need to make sure to add it to the child cache adapters.
        $this->addLogger($filesystemCache);

        return $this->createCache(ChainAdapter::class, [[$inMemoryCache, $filesystemCache]], $useInjector);
    }

    /**
     * Determine if PHP files is supported
     */
    protected function isPHPFilesSupported(): bool
    {
        static $phpFilesSupported = null;
        if (null === $phpFilesSupported) {
            // Only consider to be enabled if opcache is enabled in CLI, or else
            // filesystem cache won't be shared between webserver and CLI.
            $phpFilesSupported = PhpFilesAdapter::isSupported() &&
                filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOL);
        }
        return $phpFilesSupported;
    }

    /**
     * Instantiate the cache adapter for the filesystem cache.
     */
    private function instantiateFilesystemCache(
        string $namespace,
        int $defaultLifetime,
        string $directory,
        bool $useInjector
    ): CacheInterface|CacheItemPoolInterface {
        if ($this->isPHPFilesSupported()) {
            return $this->instantiateCache(PhpFilesAdapter::class, [$namespace, $defaultLifetime, $directory], $useInjector);
        }
        return $this->instantiateCache(FilesystemAdapter::class, [$namespace, $defaultLifetime, $directory], $useInjector);
    }

    /**
     * Instantiate the cache adapter for the in-memory cache.
     */
    private function instantiateInMemoryCache(string $service, string $inMemoryCacheFactory, array $args): CacheItemPoolInterface
    {
        if ($args['useInjector']) {
            $factory = Injector::inst()->create($inMemoryCacheFactory);
        } else {
            $factory = new $inMemoryCacheFactory();
        }
        /** @var InMemoryCacheFactory $factory */
        $adapter = $factory->createPsr6($service, $args);
        $this->addLogger($adapter);
        return $adapter;
    }
}
