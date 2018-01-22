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
            'sameDirectory' => [
                __DIR__ . '/CsvBulkLoaderTest.yml',
                './CsvBulkLoaderTest.yml',
                'Could not resolve fixture path relative from same directory',
            ],
            'filenameOnly' => [
                __DIR__ . '/CsvBulkLoaderTest.yml',
                'CsvBulkLoaderTest.yml',
                'Could not resolve fixture path from filename only',
            ],
            'parentPath' => [
                dirname(__DIR__) . '/ORM/DataObjectTest.yml',
                '../ORM/DataObjectTest.yml',
                'Could not resolve fixture path from parent path',
            ],
            'absolutePath' => [
                dirname(__DIR__) . '/ORM/DataObjectTest.yml',
                dirname(__DIR__) . '/ORM/DataObjectTest.yml',
                'Could not relsolve fixture path from absolute path',
            ],
        ];
    }

    /**
     * @dataProvider provideResolveFixturePath
     */
    public function testResolveFixturePath($expected, $path, $message)
    {
        $this->assertEquals(
            $expected,
            $this->resolveFixturePath($path),
            $message
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
            $this->assertTrue(Permission::check('ADMIN'), 'Member should now have ADMIN role');
            // check nested actAs calls work as expected
            Member::actAs(null, function () {
                $this->assertFalse(Permission::check('ADMIN'), 'Member should not act as ADMIN any more after reset');
            });
        });
    }

    /**
     * @useDatabase
     */
    public function testCreateMemberWithPermission()
    {
        $this->assertEmpty(
            Member::get()->filter(['Email' => 'TESTPERM@example.org']),
            'DB should not have the test member created when the test starts'
        );
        $this->createMemberWithPermission('TESTPERM');
        $this->assertCount(
            1,
            Member::get()->filter(['Email' => 'TESTPERM@example.org']),
            'Database should now contain the test member'
        );
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideAllMatchingList()
     *
     * @param $match
     * @param $itemsForList
     *
     * @testdox Has assertion assertListAllMatch
     */
    public function testAssertListAllMatch($match, $itemsForList, $message)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListAllMatch($match, $list, $message);
    }

    /**
     * generate SS_List as this is not possible in dataProvider
     *
     * @param array $itemsForList
     *
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
     *
     * @testdox Has assertion assertListContains
     */
    public function testAssertListContains($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

        $this->assertListContains($matches, $list, 'The list does not contain the expected items');
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
     * @testdox Has assertion assertListNotContains
     *
     * @param $matches
     * @param $itemsForList
     */
    public function testAssertListNotContains($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListNotContains($matches, $list, 'List contains forbidden items');
    }

    /**
     * @dataProvider \SilverStripe\Dev\Tests\SapphireTestTest\DataProvider::provideEqualLists
     *
     * @param $matches
     * @param $itemsForList
     *
     * @testdox assertion assertListNotContains throws a exception when a matching item is found in the list
     *
     * @expectedException \PHPUnit_Framework_ExpectationFailedException
     */
    public function testAssertListNotContainsFailsWhenListContainsAMatch($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

        $this->assertListNotContains($matches, $list);
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

        $this->assertListEquals($matches, $list, 'Lists do not equal');
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
}
