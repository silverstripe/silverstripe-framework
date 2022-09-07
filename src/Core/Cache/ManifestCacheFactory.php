<?php

namespace SilverStripe\Core\Cache;

use BadMethodCallException;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;

/**
 * Assists with building of manifest cache prior to config being available
 */
class ManifestCacheFactory extends DefaultCacheFactory
{
    public function __construct(array $args = [], LoggerInterface $logger = null)
    {
        // Build default manifest logger
        if (!$logger) {
            $logger = new Logger("manifestcache-log");
            if (Director::isDev()) {
                $logger->pushHandler(new StreamHandler('php://output'));
            } else {
                $logger->pushHandler(new ErrorLogHandler());
            }
        }

        parent::__construct($args, $logger);
    }

    /**
     * Note: While the returned object is used as a singleton (by the originating Injector->get() call),
     * this cache object shouldn't be a singleton itself - it has varying constructor args for the same service name.
     *
     * @param string $service The class name of the service.
     * @param array $params The constructor parameters.
     * @return CacheInterface
     */
    public function create($service, array $params = [])
    {
        // Override default cache generation with SS_MANIFESTCACHE
        $cacheClass = Environment::getEnv('SS_MANIFESTCACHE');
        $params['useInjector'] = false;

        if (!$cacheClass) {
            return parent::create($service, $params);
        }

        // Check if SS_MANIFESTCACHE is a factory
        if (is_a($cacheClass, CacheFactory::class, true)) {
            /** @var CacheFactory $factory */
            $factory = new $cacheClass;
            return $factory->create($service, $params);
        }

        // Check if SS_MANIFESTCACHE is a PSR-6 or PSR-16 class
        if (is_a($cacheClass, CacheItemPoolInterface::class, true) ||
            is_a($cacheClass, CacheInterface::class, true)
        ) {
            $args = array_merge($this->args, $params);
            $namespace = isset($args['namespace']) ? $args['namespace'] : '';
            return $this->createCache($cacheClass, [$namespace], false);
        }

        // Validate type
        throw new BadMethodCallException(
            'SS_MANIFESTCACHE is not a valid CacheInterface, CacheItemPoolInterface or CacheFactory class name'
        );
    }
}
