<?php

namespace SilverStripe\Security\Tests;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\CacheFactory;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\InheritedPermissionFlusher;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Tests\InheritedPermissionsFlusherTest\TestCacheFlusher;
use SilverStripe\Core\Config\Config;

class InheritedPermissionsFlusherTest extends SapphireTest
{
    protected static $fixture_file = 'InheritedPermissionsFlusherTest.yml';

    public function setUp()
    {
        parent::setUp();

        // Set up a mock cache service
        Injector::inst()->load([
            CacheInterface::class . '.TestFlusherCache' => [
                'factory' => CacheFactory::class,
                'constructor' => ['namespace' => 'TestFlusherCache']
            ]
        ]);
    }

    public function testMemberFlushesPermissions()
    {
        $cache = Injector::inst()->create(CacheInterface::class . '.TestFlusherCache');
        $flusher = new TestCacheFlusher($cache);
        $extension = new InheritedPermissionFlusher();
        $extension->setServices([$flusher]);
        Injector::inst()->registerService($extension, InheritedPermissionFlusher::class);
        $editor = $this->objFromFixture(Member::class, 'editor');
        $admin = $this->objFromFixture(Member::class, 'admin');
        $editorKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $editor->ID);
        $adminKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $admin->ID);
        $cache->set($editorKey, 'uncle');
        $cache->set($adminKey, 'cheese');
        $editor->flushCache();

        $this->assertNull($cache->get($editorKey));
        $this->assertEquals('cheese', $cache->get($adminKey));

        $admin->flushCache();
        $this->assertNull($cache->get($editorKey));
        $this->assertNull($cache->get($adminKey));
    }

    public function testGroupFlushesPermissions()
    {
        $cache = Injector::inst()->create(CacheInterface::class . '.TestFlusherCache');
        $flusher = new TestCacheFlusher($cache);
        $extension = new InheritedPermissionFlusher();
        $extension->setServices([$flusher]);
        Injector::inst()->registerService($extension, InheritedPermissionFlusher::class);
        $editors = $this->objFromFixture(Group::class, 'editors');
        $admins = $this->objFromFixture(Group::class, 'admins');

        // Populate the cache for all members in each group
        foreach ($editors->Members() as $editor) {
            $editorKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $editor->ID);
            $cache->set($editorKey, 'uncle');
        }
        foreach ($admins->Members() as $admin) {
            $adminKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $admin->ID);
            $cache->set($adminKey, 'cheese');
        }

        // Clear the cache for all members in the editors group
        $editors->flushCache();

        foreach ($editors->Members() as $editor) {
            $editorKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $editor->ID);
            $this->assertNull($cache->get($editorKey));
        }
        // Admins group should be unaffected
        foreach ($admins->Members() as $admin) {
            $adminKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $admin->ID);
            $this->assertEquals('cheese', $cache->get($adminKey));
        }


        $admins->flushCache();
        // Admins now affected
        foreach ($admins->Members() as $admin) {
            $adminKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $admin->ID);
            $this->assertNull($cache->get($adminKey));
        }
        foreach ($editors->Members() as $editor) {
            $editorKey = $flusher->generateCacheKey(TestCacheFlusher::$categories[0], $editor->ID);
            $this->assertNull($cache->get($editorKey));
        }
    }
}