<?php

namespace SilverStripe\Core\Tests\Cache;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\ApcuCacheFactory;
use SilverStripe\Core\Cache\MemcachedCacheFactory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Test\Cache\CacheTest\MockCache;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Cache\Simple\ApcuCache;
use Symfony\Component\Cache\Simple\MemcachedCache;

class CacheTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();

        Injector::inst()
            ->load([
                ApcuCacheFactory::class => [
                    'constructor' => [ 'version' => 'ss40test' ]
                ],
                MemcachedCacheFactory::class => MemcachedCacheFactory::class,
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
                ApcuCache::class => MockCache::class,
                MemcachedCache::class => MockCache::class,
            ]);
    }

    public function testApcuCacheFactory()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.TestApcuCache');
        $this->assertInstanceOf(
            MockCache::class,
            $cache
        );
        $this->assertEquals(
            [
                'TestApcuCache_' . md5(BASE_PATH),
                2600,
                'ss40test'
            ],
            $cache->getArgs()
        );
    }

    public function testMemCacheFactory()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.TestMemcache');
        $this->assertInstanceOf(
            MockCache::class,
            $cache
        );
        $this->assertEquals(
            [
                null,
                'TestMemCache_' . md5(BASE_PATH),
                5600
            ],
            $cache->getArgs()
        );
    }
}
