<?php

namespace SilverStripe\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use SilverStripe\Core\Environment;

/**
 * Factory to instantiate a MemcachedAdapter for use in caching.
 *
 * SS_MEMCACHED_DSN must be set in environment variables.
 * See https://symfony.com/doc/current/components/cache/adapters/memcached_adapter.html#configure-the-connection
 */
class MemcachedCacheFactory extends AbstractCacheFactory implements InMemoryCacheFactory
{
    /**
     * @inheritdoc
     */
    public function create(string $service, array $params = []): CacheInterface
    {
        $psr6Cache = $this->createPsr6($service, $params);
        $useInjector = isset($params['useInjector']) ? $params['useInjector'] : true;
        return $this->prepareCacheForUse($psr6Cache, $useInjector);
    }

    public function createPsr6(string $service, array $params = []): CacheItemPoolInterface
    {
        if (!MemcachedAdapter::isSupported()) {
            throw new RuntimeException('Memcached is not supported in the current environment. Cannot use Memcached cache.');
        }

        $dsn = Environment::getEnv('SS_MEMCACHED_DSN');
        if (!$dsn) {
            throw new RuntimeException('The SS_MEMCACHED_DSN environment variable must be set to use Memcached cache.');
        }

        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);
        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;
        $useInjector = isset($params['useInjector']) ? $params['useInjector'] : true;
        $client = MemcachedAdapter::createConnection($dsn);

        return $this->instantiateCache(
            MemcachedAdapter::class,
            [$client, $namespace, $defaultLifetime],
            $useInjector
        );
    }
}
