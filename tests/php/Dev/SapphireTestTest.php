<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class SapphireTestTest extends SapphireTest
{

    public function provideResolveFixturePath()
    {
        return [
            [__DIR__ . '/CsvBulkLoaderTest.yml', './CsvBulkLoaderTest.yml'], //same dir
            [__DIR__ . '/CsvBulkLoaderTest.yml', 'CsvBulkLoaderTest.yml'],  // Filename only
            [dirname(__DIR__) . '/ORM/DataObjectTest.yml', '../ORM/DataObjectTest.yml'], // Parent path
            [dirname(__DIR__) . '/ORM/DataObjectTest.yml', dirname(__DIR__) .'/ORM/DataObjectTest.yml'], // Absolute path
        ];
    }

    /**
     * @dataProvider provideResolveFixturePath
     */
    public function testResolveFixturePath($expected, $path)
    {
        $this->assertEquals(
            $expected,
            $this->resolveFixturePath($path)
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

    /**
     * @testdox Has assertion assertListAllMatch
     */
    public function testAssertListAllMatch()
    {
        $this->markTestIncomplete();
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideEqualLists
     *
     * @param $matches
     * @param $itemsForList
     * @testdox Has assertion assertListContains
     */
    public function testAssertListContains($matches, $itemsForList)
    {
        //generate List as this is not possible in dataProvider
        $list = ArrayList::create();
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));
        foreach ($itemsForList as $data) {
            $list->push(Member::create($data));
        }

        $this->assertListContains($matches, $list);
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideNotContainingList
     * @testdox assertion assertListEquals fails on non equal Lists
     *
     * @param $matches
     * @param $itemsForList array
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertListContainsFailsIfListDoesNotContainMatch($matches, $itemsForList)
    {
        //generate List as this is not possible in dataProvider
        $list = ArrayList::create();
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));
        foreach ($itemsForList as $data) {
            $list->push(Member::create($data));
        }

        $this->assertListContains($matches, $list);
    }

    /**
     * @testdox Has assertion assertNotListContains
     */
    public function testAssertNotListContains()
    {
        $this->markTestIncomplete();
    }


    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideEqualLists
     * @testdox Has assertion assertListEquals
     *
     * @param $matches
     * @param $itemsForList
     */
    public function testAssertListEquals($matches, $itemsForList)
    {
        //generate List as this is not possible in dataProvider
        $list = ArrayList::create();
        foreach ($itemsForList as $data) {
            $list->push(Member::create($data));
        }

        $this->assertListEquals($matches, $list);
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideNonEqualLists
     * @testdox assertion assertListEquals fails on non equal Lists
     *
     * @param $matches
     * @param $itemsForList
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertListEqualsFailsOnNonEqualLists($matches, $itemsForList)
    {
        //generate List as this is not possible in dataProvider
        $list = ArrayList::create();
        foreach ($itemsForList as $data) {
            $list->push(Member::create($data));
        }

        $this->assertListEquals($matches, $list);
    }
}
