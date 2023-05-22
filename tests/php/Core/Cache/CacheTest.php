<?php

namespace SilverStripe\Core\Tests\Cache;

use Behat\Gherkin\Cache\MemoryCache;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\ApcuCacheFactory;
use SilverStripe\Core\Cache\MemcachedCacheFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Tests\Cache\CacheTest\MockCache;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Memcached;

class CacheTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()
            ->load([
                ApcuCacheFactory::class => [
                    'constructor' => [ 'version' => 'ss40test' ]
                ],
                'MemcachedClient' => Memcached::class,
                MemcachedCacheFactory::class => [
                    'constructor' => [ 'memcachedClient' => '%$MemcachedClient' ]
                ],
                CacheInterface::class . '.TestApcuCache' =>  [
                    'factory' => ApcuCacheFactory::class,
                    'constructor' => [
                        'namespace' => 'TestApcuCache',
                        'defaultLifetime' => 2600,
                    ],
                ],
                CacheInterface::class . '.TestMemcache' => [
                    'factory' => MemcachedCacheFactory::class,
                    'constructor' => [
                        'namespace' => 'TestMemCache',
                        'defaultLifetime' => 5600,
                    ],
                ],
                Psr16Cache::class => MockCache::class,
                ApcuAdapter::class => MockCache::class,
                MemcachedAdapter::class => MockCache::class,
            ]);
    }

    public function testApcuCacheFactory()
    {
        $psr16Cache = Injector::inst()->get(CacheInterface::class . '.TestApcuCache');
        $this->assertInstanceOf(MockCache::class, $psr16Cache);
        $this->assertEquals(MockCache::class, get_class($psr16Cache->getArgs()[0]));
        $this->assertEquals(
            [
                'TestApcuCache_' . md5(BASE_PATH),
                2600,
                'ss40test'
            ],
            $psr16Cache->getArgs()[0]->getArgs()
        );
    }

    public function testMemCacheFactory()
    {
        if (!class_exists(Memcached::class)) {
            $this->markTestSkipped('Memcached is not installed');
        }
        $psr16Cache = Injector::inst()->get(CacheInterface::class . '.TestMemcache');
        $this->assertInstanceOf(MockCache::class, $psr16Cache);
        $this->assertEquals(MockCache::class, get_class($psr16Cache->getArgs()[0]));
        $this->assertEquals(
            [
                new MemCached(),
                'TestMemCache_' . md5(BASE_PATH),
                5600
            ],
            $psr16Cache->getArgs()[0]->getArgs()
        );
    }
}
