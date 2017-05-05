<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\FixtureBlueprint;
use SilverStripe\Dev\FixtureFactory;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\FixtureFactoryTest\DataObjectRelation;
use SilverStripe\Dev\Tests\FixtureFactoryTest\TestDataObject;

class FixtureFactoryTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected static $extra_dataobjects = array(
        TestDataObject::class,
        DataObjectRelation::class
    );

    public function testCreateRaw()
    {
        $factory = new FixtureFactory();
        $id = $factory->createRaw(
            TestDataObject::singleton()->baseTable(),
            'one',
            array('Name' => 'My Name')
        );
        $this->assertNotNull($id);
        $this->assertGreaterThan(0, $id);
        $obj = TestDataObject::get()->find('ID', $id);
        $this->assertNotNull($obj);
        $this->assertEquals('My Name', $obj->Name);
    }

    public function testSetId()
    {
        $factory = new FixtureFactory();
        $obj = new TestDataObject();
        $obj->write();
        $factory->setId(TestDataObject::class, 'one', $obj->ID);
        $this->assertEquals(
            $obj->ID,
            $factory->getId(TestDataObject::class, 'one')
        );
    }

    public function testGetId()
    {
        $factory = new FixtureFactory();
        $obj = $factory->createObject(TestDataObject::class, 'one');
        $this->assertEquals(
            $obj->ID,
            $factory->getId(TestDataObject::class, 'one')
        );
    }

    public function testGetIds()
    {
        $factory = new FixtureFactory();
        $obj = $factory->createObject(TestDataObject::class, 'one');
        $this->assertEquals(
            array('one' => $obj->ID),
            $factory->getIds(TestDataObject::class)
        );
    }

    public function testDefine()
    {
        $factory = new FixtureFactory();
        $this->assertFalse($factory->getBlueprint(TestDataObject::class));
        $factory->define(TestDataObject::class);
        $this->assertInstanceOf(
            FixtureBlueprint::class,
            $factory->getBlueprint(TestDataObject::class)
        );
    }

    public function testDefineWithCustomBlueprint()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);
        $factory = new FixtureFactory();
        $this->assertFalse($factory->getBlueprint(TestDataObject::class));
        $factory->define(TestDataObject::class, $blueprint);
        $this->assertInstanceOf(
            FixtureBlueprint::class,
            $factory->getBlueprint(TestDataObject::class)
        );
        $this->assertEquals(
            $blueprint,
            $factory->getBlueprint(TestDataObject::class)
        );
    }

    public function testDefineWithDefaults()
    {
        $factory = new FixtureFactory();
        $factory->define(TestDataObject::class, array('Name' => 'Default'));
        $obj = $factory->createObject(TestDataObject::class, 'one');
        $this->assertEquals('Default', $obj->Name);
    }

    public function testDefineMultipleBlueprintsForClass()
    {
        $factory = new FixtureFactory();
        $factory->define(
            TestDataObject::class,
            new FixtureBlueprint(TestDataObject::class)
        );
        $factory->define(
            'FixtureFactoryTest_DataObjectWithDefaults',
            new FixtureBlueprint(
                'FixtureFactoryTest_DataObjectWithDefaults',
                TestDataObject::class,
                array('Name' => 'Default')
            )
        );

        $obj = $factory->createObject(TestDataObject::class, 'one');
        $this->assertNull($obj->Name);

        $objWithDefaults = $factory->createObject('FixtureFactoryTest_DataObjectWithDefaults', 'two');
        $this->assertEquals('Default', $objWithDefaults->Name);

        $this->assertEquals(
            $obj->ID,
            $factory->getId(TestDataObject::class, 'one')
        );
        $this->assertEquals(
            $objWithDefaults->ID,
            $factory->getId(TestDataObject::class, 'two'),
            'Can access fixtures under class name, not blueprint name'
        );
    }

    public function testClear()
    {
        $factory = new FixtureFactory();
        $obj1Id = $factory->createRaw(
            TestDataObject::singleton()->baseTable(),
            'one',
            array('Name' => 'My Name')
        );
        $obj2 = $factory->createObject(TestDataObject::class, 'two');

        $factory->clear();

        $this->assertFalse($factory->getId(TestDataObject::class, 'one'));
        $this->assertNull(TestDataObject::get()->byID($obj1Id));
        $this->assertFalse($factory->getId(TestDataObject::class, 'two'));
        $this->assertNull(TestDataObject::get()->byID($obj2->ID));
    }

    public function testClearWithClass()
    {
        $factory = new FixtureFactory();
        $obj1 = $factory->createObject(TestDataObject::class, 'object-one');
        $relation1 = $factory->createObject(DataObjectRelation::class, 'relation-one');

        $factory->clear(TestDataObject::class);

        $this->assertFalse(
            $factory->getId(TestDataObject::class, 'one')
        );
        $this->assertNull(TestDataObject::get()->byID($obj1->ID));
        $this->assertEquals(
            $relation1->ID,
            $factory->getId(DataObjectRelation::class, 'relation-one')
        );
        $this->assertInstanceOf(
            DataObjectRelation::class,
            DataObjectRelation::get()->byID($relation1->ID)
        );
    }

    public function testGetByClassOrTable()
    {
        $factory = new FixtureFactory();
        $obj1 = $factory->createObject(TestDataObject::class, 'object-one', [ 'Name' => 'test one' ]);
        $this->assertInstanceOf(TestDataObject::class, $factory->get(TestDataObject::class, 'object-one'));
        $this->assertEquals('test one', $factory->get(TestDataObject::class, 'object-one')->Name);

        $obj2 = $factory->createRaw('FixtureFactoryTest_TestDataObject', 'object-two', [ 'Name' => 'test two' ]);
        $this->assertInstanceOf(
            TestDataObject::class,
            $factory->get('FixtureFactoryTest_TestDataObject', 'object-two')
        );
        $this->assertEquals('test two', $factory->get('FixtureFactoryTest_TestDataObject', 'object-two')->Name);
    }
}
