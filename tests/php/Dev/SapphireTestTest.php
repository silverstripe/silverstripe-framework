<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class SapphireTestTest extends SapphireTest
{

    /**
     * @return array
     */
    public function provideResolveFixturePath()
    {
        return [
            [__DIR__ . '/CsvBulkLoaderTest.yml', './CsvBulkLoaderTest.yml'],
            //same dir
            [__DIR__ . '/CsvBulkLoaderTest.yml', 'CsvBulkLoaderTest.yml'],
            // Filename only
            [dirname(__DIR__) . '/ORM/DataObjectTest.yml', '../ORM/DataObjectTest.yml'],
            // Parent path
            [dirname(__DIR__) . '/ORM/DataObjectTest.yml', dirname(__DIR__) . '/ORM/DataObjectTest.yml'],
            // Absolute path
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

    /**
     * @useDatabase
     */
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

    /**
     * @useDatabase
     */
    public function testCreateMemberWithPermission()
    {
        $this->assertCount(0, Member::get()->filter(['Email' => 'TESTPERM@example.org']));
        $this->createMemberWithPermission('TESTPERM');
        $this->assertCount(1, Member::get()->filter(['Email' => 'TESTPERM@example.org']));
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideAllMatchingList()
     *
     * @param $match
     * @param $itemsForList
     * @testdox Has assertion assertListAllMatch
     */
    public function testAssertListAllMatch($match, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListAllMatch($match, $list);
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideNotMatchingList()
     *
     * @param $match
     * @param $itemsForList
     *
     * @testdox assertion assertListAllMatch fails when not all items are matching
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertListAllMatchFailsWhenNotMatchingAllItems($match, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListAllMatch($match, $list);
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideEqualListsWithEmptyList()
     *
     * @param $matches
     * @param $itemsForList
     * @testdox Has assertion assertListContains
     */
    public function testAssertListContains($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

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
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

        $this->assertListContains($matches, $list);
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideNotContainingList
     *
     * @testdox Has assertion assertNotListContains
     *
     * @param $matches
     * @param $itemsForList
     */
    public function testAssertNotListContains($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertNotListContains($matches, $list);
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideEqualLists
     *
     * @param $matches
     * @param $itemsForList
     * @testdox assertion assertNotListContains throws a exception when a matching item is found in the list
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertNotListContainsFailsWhenListContainsAMatch($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

        $this->assertNotListContains($matches, $list);
    }


    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideEqualListsWithEmptyList()
     * @testdox Has assertion assertListEquals
     *
     * @param $matches
     * @param $itemsForList
     */
    public function testAssertListEquals($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

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
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListEquals($matches, $list);
    }

    /**
     * generate SS_List as this is not possible in dataProvider
     *
     * @param $itemsForList array
     * @return ArrayList
     */
    private function generateArrayListFromItems($itemsForList)
    {
        $list = ArrayList::create();
        foreach ($itemsForList as $data) {
            $list->push(Member::create($data));
        }
        return $list;
    }
}
