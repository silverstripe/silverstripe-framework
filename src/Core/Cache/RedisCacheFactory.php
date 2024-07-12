<?php

namespace SilverStripe\Core\Cache;

use RuntimeException;
use Predis\Client as PredisClient;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Relay\Relay;
use SilverStripe\Core\Environment;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Factory to instantiate a RedisAdapter for use in caching.
 *
 * One of the "redis" PHP extension, the "predis/predis" package, or the "cachewerk/relay" package is required to use Redis.
 *
 * SS_REDIS_DSN must be set in environment variables.
 * See https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#configure-the-connection
 */
class RedisCacheFactory extends AbstractCacheFactory implements InMemoryCacheFactory
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
        if (!$this->isSupported()) {
            throw new RuntimeException('Redis is not supported in the current environment. Cannot use Redis cache.');
        }

        $dsn = Environment::getEnv('SS_REDIS_DSN');
        if (!$dsn) {
            throw new RuntimeException('The SS_REDIS_DSN environment variable must be set to use Redis cache.');
        }

        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);
        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;
        $useInjector = isset($params['useInjector']) ? $params['useInjector'] : true;
        $client = RedisAdapter::createConnection($dsn, ['lazy' => true]);

        return $this->instantiateCache(
            RedisAdapter::class,
            [$client, $namespace, $defaultLifetime],
            $useInjector
        );
    }

    public function isSupported()
    {
        return class_exists(PredisClient::class) ||
            class_exists(Relay::class) ||
            extension_loaded('redis');
    }
}
