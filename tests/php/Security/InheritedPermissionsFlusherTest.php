<?php

namespace SilverStripe\Security\Tests;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\InheritedPermissionFlusher;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Test\InheritedPermissionsTest\TestPermissionNode;
use ReflectionClass;

class InheritedPermissionsFlusherTest extends SapphireTest
{
    protected static $fixture_file = 'InheritedPermissionsTest.yml';

    protected static $extra_dataobjects = [
        TestPermissionNode::class,
    ];

    public function testMemberFlushesPermissions()
    {
        $cache = Injector::inst()->create(CacheInterface::class . '.InheritedPermissions');
        $permissions = new InheritedPermissions(TestPermissionNode::class, $cache);
        $extension = new InheritedPermissionFlusher();
        $extension->setServices([$permissions]);
        Injector::inst()->registerService($extension, InheritedPermissionFlusher::class);
        $editor = $this->objFromFixture(Member::class, 'editor');
        $admin = $this->objFromFixture(Member::class, 'admin');
        $editorKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $editor->ID);
        $adminKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $admin->ID);
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
        $cache = Injector::inst()->create(CacheInterface::class . '.InheritedPermissions');
        $permissions = new InheritedPermissions(TestPermissionNode::class, $cache);
        $extension = new InheritedPermissionFlusher();
        $extension->setServices([$permissions]);
        Injector::inst()->registerService($extension, InheritedPermissionFlusher::class);
        $editors = $this->objFromFixture(Group::class, 'editors');
        $admins = $this->objFromFixture(Group::class, 'admins');

        // Populate the cache for all members in each group
        foreach ($editors->Members() as $editor) {
            $editorKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $editor->ID);
            $cache->set($editorKey, 'uncle');
        }
        foreach ($admins->Members() as $admin) {
            $adminKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $admin->ID);
            $cache->set($adminKey, 'cheese');
        }

        // Clear the cache for all members in the editors group
        $editors->flushCache();

        foreach ($editors->Members() as $editor) {
            $editorKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $editor->ID);
            $this->assertNull($cache->get($editorKey));
        }
        // Admins group should be unaffected
        foreach ($admins->Members() as $admin) {
            $adminKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $admin->ID);
            $this->assertEquals('cheese', $cache->get($adminKey));
        }


        $admins->flushCache();
        // Admins now affected
        foreach ($admins->Members() as $admin) {
            $adminKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $admin->ID);
            $this->assertNull($cache->get($adminKey));
        }
        foreach ($editors->Members() as $editor) {
            $editorKey = $this->generateCacheKey($permissions, InheritedPermissions::EDIT, $editor->ID);
            $this->assertNull($cache->get($editorKey));
        }

    }

    protected function generateCacheKey(InheritedPermissions $inst, $type, $memberID)
    {
        $reflection = new ReflectionClass(InheritedPermissions::class);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        return $method->invokeArgs($inst, [$type, $memberID]);
    }

}