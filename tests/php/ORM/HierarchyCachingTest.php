<?php

namespace SilverStripe\Versioned\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\Tests\HierarchyTest\TestObject;
use SilverStripe\ORM\Tests\HierarchyTest\HideTestObject;
use SilverStripe\ORM\Tests\HierarchyTest\HideTestSubObject;

/**
 * @internal Only test the right values are returned, not that the cache is actually used.
 */
class HierachyCacheTest extends SapphireTest
{

    protected static $fixture_file = 'HierarchyTest.yml';

    protected static $extra_dataobjects = array(
        TestObject::class,
        HideTestObject::class,
        HideTestSubObject::class,
    );

    public function setUp(): void
    {
        parent::setUp();
        TestObject::singleton()->flushCache();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        HideTestObject::config()->update(
            'hide_from_hierarchy',
            [ HideTestSubObject::class ]
        );
    }

    public function cacheNumChildrenDataProvider()
    {
        return [
            [TestObject::class, 'obj1', false, 0, 'childless object should have a numChildren of 0'],
            [TestObject::class, 'obj1', true, 0, 'childless object should have a numChildren of 0 when cache'],
            [TestObject::class, 'obj2', false, 2, 'Root object numChildren should count direct children'],
            [TestObject::class, 'obj2', true, 2, 'Root object numChildren should count direct children when cache'],
            [TestObject::class, 'obj3a', false, 2, 'Sub object numChildren should count direct children'],
            [TestObject::class, 'obj3a', true, 2, 'Sub object numChildren should count direct children when cache'],
            [TestObject::class, 'obj3d', false, 0, 'Childess Sub object numChildren should be 0'],
            [TestObject::class, 'obj3d', true, 0, 'Childess Sub object numChildren should be 0 when cache'],
            [HideTestObject::class, 'obj4', false, 1, 'Hidden object should not be included in count'],
            [HideTestObject::class, 'obj4', true, 1, 'Hidden object should not be included in couunt when cache']
        ];
    }


    /**
     * @dataProvider cacheNumChildrenDataProvider
     */
    public function testNumChildrenCache($className, $identifier, $cache, $expected, $message)
    {
        $node = $this->objFromFixture($className, $identifier);

        $actual = $node->numChildren($cache);

        $this->assertEquals($expected, $actual, $message);

        if ($cache) {
            // When caching is eanbled, try re-accessing the numChildren value to make sure it doesn't change.
            $actual = $node->numChildren($cache);
            $this->assertEquals($expected, $actual, $message);
        }
    }

    public function prepopulateCacheNumChildrenDataProvider()
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

    /**
     * @dataProvider prepopulateCacheNumChildrenDataProvider
     */
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
