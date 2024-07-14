<?php

namespace SilverStripe\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

/**
 * Factory to instantiate an ApcuAdapter for use in caching.
 *
 * Note that APCu cache may not be shared between your webserver and the CLI.
 * Flushing the cache from your terminal may not flush the cache used by the webserver.
 * See https://github.com/symfony/symfony/discussions/54066
 */
class ApcuCacheFactory extends AbstractCacheFactory implements InMemoryCacheFactory
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
            throw new RuntimeException('APCu is not supported in the current environment. Cannot use APCu cache.');
        }

        $namespace = isset($params['namespace'])
            ? $params['namespace'] . '_' . md5(BASE_PATH)
            : md5(BASE_PATH);
        $defaultLifetime = isset($params['defaultLifetime']) ? $params['defaultLifetime'] : 0;
        // $version is optional - defaults to null.
        $version = isset($params['version']) ? $params['version'] : Environment::getEnv('SS_APCU_VERSION');
        if ($version === false) {
            $version = null;
        }
        $useInjector = isset($params['useInjector']) ? $params['useInjector'] : true;

        return $this->instantiateCache(
            ApcuAdapter::class,
            [$namespace, $defaultLifetime, $version],
            $useInjector
        );
    }

    private function isSupported(): bool
    {
        static $isSupported = null;
        if (null === $isSupported) {
            // Need to check for CLI because Symfony won't: https://github.com/symfony/symfony/pull/25080
            $isSupported = Director::is_cli()
                ? filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOL) && ApcuAdapter::isSupported()
                : ApcuAdapter::isSupported();
        }
        return $isSupported;
    }
}
