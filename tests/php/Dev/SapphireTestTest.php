<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class SapphireTestTest extends SapphireTest
{
    public function testResolveFixturePath()
    {
        // Same directory
        $this->assertEquals(
            __DIR__ . '/CsvBulkLoaderTest.yml',
            $this->resolveFixturePath('./CsvBulkLoaderTest.yml')
        );
        // Filename only
        $this->assertEquals(
            __DIR__ . '/CsvBulkLoaderTest.yml',
            $this->resolveFixturePath('CsvBulkLoaderTest.yml')
        );
        // Parent path
        $this->assertEquals(
            dirname(__DIR__) . '/ORM/DataObjectTest.yml',
            $this->resolveFixturePath('../ORM/DataObjectTest.yml')
        );
        // Absolute path
        $this->assertEquals(
            dirname(__DIR__) . '/ORM/DataObjectTest.yml',
            $this->resolveFixturePath(dirname(__DIR__) .'/ORM/DataObjectTest.yml')
        );
    }

    public function testActWithPermission()
    {
        $this->logOut();
        $this->assertFalse(Permission::check('ADMIN'));
        $this->actWithPermission('ADMIN', function () {
            $this->assertTrue(Permission::check('ADMIN'));
            // check nested actAs calls work as expected
            Member::actAs(null, function () {
                $this->assertFalse(Permission::check('ADMIN'));
            });
        });
    }

    public function testCreateMemberWithPermission()
    {
        $this->assertCount(0, Member::get()->filter([ 'Email' => 'TESTPERM@example.org' ]));
        $this->createMemberWithPermission('TESTPERM');
        $this->assertCount(1, Member::get()->filter([ 'Email' => 'TESTPERM@example.org' ]));
    }
}
