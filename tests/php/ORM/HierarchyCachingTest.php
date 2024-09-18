<?php

namespace SilverStripe\Versioned\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\Tests\HierarchyTest\TestObject;
use SilverStripe\ORM\Tests\HierarchyTest\HideTestObject;
use SilverStripe\ORM\Tests\HierarchyTest\HideTestSubObject;
use SilverStripe\ORM\Tests\HierarchyTest\HierarchyOnSubclassTestObject;
use SilverStripe\ORM\Tests\HierarchyTest\HierarchyOnSubclassTestSubObject;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal Only test the right values are returned, not that the cache is actually used.
 */
class HierarchyCachingTest extends SapphireTest
{

    protected static $fixture_file = 'HierarchyTest.yml';

    protected static $extra_dataobjects = [
        TestObject::class,
        HideTestObject::class,
        HideTestSubObject::class,
        HierarchyOnSubclassTestObject::class,
        HierarchyOnSubclassTestSubObject::class
    ];

    protected function setUp(): void
    {
        parent::setUp();
        TestObject::singleton()->flushCache();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        HideTestObject::config()->merge(
            'hide_from_hierarchy',
            [ HideTestSubObject::class ]
        );
    }

    public static function cacheNumChildrenDataProvider()
    {
        return [
            [TestObject::class, 'obj1', false, 0, 'childless object should have a numChildren of 0'],
            [TestObject::class, 'obj1', true, 0, 'childless object should have a numChildren of 0 when cache'],
            [TestObject::class, 'obj2', false, 2, 'Root object numChildren should count direct children'],
            [TestObject::class, 'obj2', true, 2, 'Root object numChildren should count direct children when cache'],
            [TestObject::class, 'obj3a', false, 2, 'Sub object numChildren should count direct children'],
            [TestObject::class, 'obj3a', true, 2, 'Sub object numChildren should count direct children when cache'],
            [TestObject::class, 'obj3d', false, 0, 'Childless Sub object numChildren should be 0'],
            [TestObject::class, 'obj3d', true, 0, 'Childless Sub object numChildren should be 0 when cache'],
            [HideTestObject::class, 'obj4', false, 1, 'Hidden object should not be included in count'],
            [HideTestObject::class, 'obj4', true, 1, 'Hidden object should not be included in count when cache']
        ];
    }


    #[DataProvider('cacheNumChildrenDataProvider')]
    public function testNumChildrenCache($className, $identifier, $cache, $expected, $message)
    {
        $node = $this->objFromFixture($className, $identifier);

        $actual = $node->numChildren($cache);

        $this->assertEquals($expected, $actual, $message);

        if ($cache) {
            // When caching is enabled, try re-accessing the numChildren value to make sure it doesn't change.
            $actual = $node->numChildren($cache);
            $this->assertEquals($expected, $actual, $message);
        }
    }

    public static function prepopulateCacheNumChildrenDataProvider()
    {
        return [
            [
                TestObject::class, [],
                'obj1', false, 0, 'childless object should have a numChildren of 0'
            ],
            [
                TestObject::class, [],
               'obj1', true, 0, 'childless object should have a numChildren of 0 when cache'
            ],
            [
                TestObject::class, [2],
                'obj1', false, 0, 'childless object should have a numChildren of 0'
            ],
            [
                TestObject::class, [2],
                'obj1', true, 0, 'childless object should have a numChildren of 0 when cache'
            ],
            [
                TestObject::class, [],
                'obj2', false, 2, 'Root object numChildren should count direct children'
            ],
            [
                TestObject::class, [],
                'obj2', true, 2, 'Root object numChildren should count direct children when cache'
            ],
            [
                TestObject::class, [2],
                'obj2', false, 2, 'Root object numChildren should count direct children'
            ],
            [
                TestObject::class, [2],
                'obj2', true, 2, 'Root object numChildren should count direct children when cache'
            ],
            [
                HideTestObject::class, [],
                'obj4', false, 1, 'Hidden object should not be included in count'
            ],
            [
                HideTestObject::class, [],
                'obj4', true, 1, 'Hidden object should not be included in count when cache'
            ],
            [
                HideTestObject::class, [2],
                'obj4', false, 1, 'Hidden object should not be included in count'
            ],
            [
                HideTestObject::class, [2],
                'obj4', true, 1, 'Hidden object should not be included in count when cache'
            ]
        ];
    }

    #[DataProvider('prepopulateCacheNumChildrenDataProvider')]
    public function testPrepopulatedNumChildrenCache(
        $className,
        $idList,
        $identifier,
        $cache,
        $expected,
        $message
    ) {
        DataObject::singleton($className)->prepopulateTreeDataCache($idList, ['numChildrenMethod' => 'numChildren']);
        $node = $this->objFromFixture($className, $identifier);

        $actual = $node->numChildren($cache);

        $this->assertEquals($expected, $actual, $message);
    }
}
