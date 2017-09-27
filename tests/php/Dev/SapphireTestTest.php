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
     * @dataProvider provideEqualLists
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

    public function testAssertListContainsFailsIfListDoesNotContainMatch()
    {
        $this->markTestIncomplete();
    }

    /**
     * @testdox Has assertion assertNotListContains
     */
    public function testAssertNotListContains()
    {
        $this->markTestIncomplete();
    }

    public function provideEqualLists()
    {
        $oneItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
        ];
        $twoItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
            ['FirstName' => 'Sam', 'Surname' => 'Minnee']
        ];

        return [
            [ //empty list
                [],
                []
            ],
            [
                [ //one param
                    ['FirstName' => 'Ingo']
                ]
                , $oneItemList
            ],
            [
                [ //two params
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
                ],
                $oneItemList
            ],
            [ //only one param
                [
                    ['FirstName' => 'Ingo'],
                    ['FirstName' => 'Sam']
                ]
                , $twoItemList
            ],
            [
                [ //two params
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'Minnee']
                ],
                $twoItemList
            ],
            [
                [ //mixed
                    ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam']
                ],
                $twoItemList
            ],
        ];
    }
    public function provideNonEqualLists()
    {
        $oneItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer']
        ];
        $twoItemList = [
            ['FirstName' => 'Ingo', 'Surname' => 'Schommer'],
            ['FirstName' => 'Sam', 'Surname' => 'Minnee']
        ];

        return [
            [ //empty list
                [
                    ['FirstName' => 'Ingo']
                ],
                []
            ],
            [
                [ //one item expected
                    ['FirstName' => 'Ingo']
                ]
                , $twoItemList
            ],
            [ //one item with wrong param
                [
                    ['FirstName' => 'IngoXX'],
                    ['FirstName' => 'Sam']
                ]
                , $twoItemList
            ],
            [
                [ //two params wrong
                    ['FirstName' => 'IngoXXX', 'Surname' => 'Schommer'],
                    ['FirstName' => 'Sam', 'Surname' => 'MinneeXXX']
                ],
                $twoItemList
            ],
            [
                [ //mixed
                    ['FirstName' => 'Daniel', 'Surname' => 'Foo'],
                    ['FirstName' => 'Dan']
                ],
                $twoItemList
            ],
        ];
    }


    /**
     * @dataProvider provideEqualLists
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
     * @dataProvider provideNonEqualLists
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
