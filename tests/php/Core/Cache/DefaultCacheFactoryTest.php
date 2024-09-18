<?php

namespace SilverStripe\Core\Tests\Cache;

use Monolog\Logger;
use Predis\Client as PredisClient;
use ReflectionProperty;
use Relay\Relay;
use RuntimeException;
use SilverStripe\Core\Cache\ApcuCacheFactory;
use SilverStripe\Core\Cache\DefaultCacheFactory;
use SilverStripe\Core\Cache\MemcachedCacheFactory;
use SilverStripe\Core\Cache\RedisCacheFactory;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use PHPUnit\Framework\Attributes\DataProvider;

class DefaultCacheFactoryTest extends SapphireTest
{
    public static function provideCreate(): array
    {
        $scenarios = [
            [
                'args' => [
                    'useInMemoryCache' => true,
                    'useInjector' => true,
                ],
                'inMemoryCacheFactory' => null,
            ],
            [
                'args' => [
                    'useInMemoryCache' => false,
                ],
                'inMemoryCacheFactory' => null,
            ],
            [
                'args' => [
                    'useInjector' => false,
                ],
                'inMemoryCacheFactory' => null,
            ],
        ];
        $cacheFactories = [
            null,
        ];
        // Add the in-memory cache factories the current test environment supports
        // If a factory isn't supported and we add it anyway it'll throw an exception.
        if (filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOL)) {
            $cacheFactories[] = ApcuCacheFactory::class;
        }
        if (MemcachedAdapter::isSupported()) {
            $cacheFactories[] = MemcachedCacheFactory::class;
        }
        if (class_exists(PredisClient::class) ||
            class_exists(Relay::class) ||
            extension_loaded('redis')
        ) {
            $cacheFactories[] = RedisCacheFactory::class;
        }
        // Use all of the above test scenarios with each supported cache factory
        $allScenarios = [];
        foreach ($cacheFactories as $cacheFactory) {
            foreach ($scenarios as $scenario) {
                $scenario['inMemoryCacheFactory'] = $cacheFactory;
                $allScenarios[] = $scenario;
            }
        }
        return $allScenarios;
    }

    #[DataProvider('provideCreate')]
    public function testCreate(array $args, ?string $inMemoryCacheFactory): void
    {
        $oldFactoryValue = Environment::getEnv('SS_IN_MEMORY_CACHE_FACTORY');
        $oldMemcachedDSNValue = Environment::getEnv('SS_MEMCACHED_DSN');
        $oldRedisDSNValue = Environment::getEnv('SS_REDIS_DSN');
        Environment::setEnv('SS_IN_MEMORY_CACHE_FACTORY', $inMemoryCacheFactory);
        // These are obviously not real connections, but it seems a real connection is not required
        // to just instantiate the cache adapter, which allows us to validate the correct adapter
        // is instantiated.
        Environment::setEnv('SS_MEMCACHED_DSN', "memcached://example.com:1234");
        Environment::setEnv('SS_REDIS_DSN', "redis://password@example.com:1234");

        try {
            $logger = new Logger('test-cache');
            $defaultArgs = [
                'namespace' => __FUNCTION__,
                'directory' => TEMP_PATH,
            ];
            $factory = new DefaultCacheFactory($defaultArgs, $logger);
            $psr16Wrapper = $factory->create('test-cache', $args);

            $reflectionPoolProperty = new ReflectionProperty($psr16Wrapper, 'pool');
            $reflectionPoolProperty->setAccessible(true);
            $cacheBucket = $reflectionPoolProperty->getValue($psr16Wrapper);

            if (!$inMemoryCacheFactory || (isset($args['useInMemoryCache']) && !$args['useInMemoryCache'])) {
                $filesystemCache = $cacheBucket;
            } else {
                $this->assertInstanceOf(ChainAdapter::class, $cacheBucket);

                $reflectionAdaptersProperty = new ReflectionProperty($cacheBucket, 'adapters');
                $reflectionAdaptersProperty->setAccessible(true);
                $adapters = $reflectionAdaptersProperty->getValue($cacheBucket);

                $this->assertCount(2, $adapters);

                // in-memory cache always comes first
                $inMemoryCache = array_shift($adapters);
                $filesystemCache = array_shift($adapters);

                // Check we have the right adapter for the given factory
                switch ($inMemoryCacheFactory) {
                    case RedisCacheFactory::class:
                        $this->assertInstanceOf(RedisAdapter::class, $inMemoryCache);
                        break;
                    case MemcachedCacheFactory::class:
                        $this->assertInstanceOf(MemcachedAdapter::class, $inMemoryCache);
                        break;
                    case ApcuCacheFactory::class:
                        $this->assertInstanceOf(ApcuAdapter::class, $inMemoryCache);
                        break;
                    default:
                        throw new RuntimeException("Unexpected factory while running test: $inMemoryCacheFactory");
                }

                // Check the adapter got the right logger
                $reflectionLoggerProperty = new ReflectionProperty($inMemoryCache, 'logger');
                $reflectionLoggerProperty->setAccessible(true);
                $this->assertTrue($logger === $reflectionLoggerProperty->getValue($inMemoryCache));
            }

            // Check filesystem cache is correct
            if (filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL) && filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOL)) {
                $this->assertInstanceOf(PhpFilesAdapter::class, $filesystemCache);
            } else {
                $this->assertInstanceOf(FilesystemAdapter::class, $filesystemCache);
            }
            // Check the adapter got the right logger
            $reflectionLoggerProperty = new ReflectionProperty($filesystemCache, 'logger');
            $reflectionLoggerProperty->setAccessible(true);
            $this->assertTrue($logger === $reflectionLoggerProperty->getValue($filesystemCache));
        } finally {
            Environment::setEnv('SS_IN_MEMORY_CACHE_FACTORY', $oldFactoryValue);
            Environment::setEnv('SS_MEMCACHED_DSN', $oldMemcachedDSNValue);
            Environment::setEnv('SS_REDIS_DSN', $oldRedisDSNValue);
        }
    }
}
