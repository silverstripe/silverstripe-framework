<?php

namespace SilverStripe\Dev\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\Tests\YamlFixtureTest\TestDataObject;
use SilverStripe\Dev\Tests\YamlFixtureTest\DataObjectRelation;
use SilverStripe\Dev\YamlFixture;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FixtureFactory;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Director;

class YamlFixtureTest extends SapphireTest
{

    protected static $extra_dataobjects = array(
        TestDataObject::class,
        DataObjectRelation::class,
    );

    public function testAbsoluteFixturePath()
    {
        $absPath = __DIR__ . '/YamlFixtureTest.yml';
        $obj = Injector::inst()->create(YamlFixture::class, $absPath);
        $this->assertEquals($absPath, $obj->getFixtureFile());
        $this->assertNull($obj->getFixtureString());
    }

    public function testRelativeFixturePath()
    {
        $relPath = ltrim(FRAMEWORK_DIR . '/tests/php/Dev/YamlFixtureTest.yml', '/');
        $obj = Injector::inst()->create(YamlFixture::class, $relPath);
        $this->assertEquals(Director::baseFolder() . '/' . $relPath, $obj->getFixtureFile());
        $this->assertNull($obj->getFixtureString());
    }

    public function testStringFixture()
    {
        $absPath = __DIR__ . '/YamlFixtureTest.yml';
        $string = file_get_contents($absPath);
        $obj = Injector::inst()->create(YamlFixture::class, $string);
        $this->assertEquals($string, $obj->getFixtureString());
        $this->assertNull($obj->getFixtureFile());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFailsWithInvalidFixturePath()
    {
        $invalidPath = ltrim(FRAMEWORK_DIR . '/tests/testing/invalid.yml', '/');
        $obj = Injector::inst()->create(YamlFixture::class, $invalidPath);
    }

    public function testSQLInsert()
    {
        $factory = new FixtureFactory();
        $absPath = __DIR__ . '/YamlFixtureTest.yml';
        $fixture = Injector::inst()->create(YamlFixture::class, $absPath);
        $fixture->writeInto($factory);

        $this->assertGreaterThan(0, $factory->getId(TestDataObject::class, "testobject1"));
        $object1 = DataObject::get_by_id(
            TestDataObject::class,
            $factory->getId(TestDataObject::class, "testobject1")
        );
        $this->assertTrue(
            $object1->ManyManyRelation()->Count() == 2,
            "Should be two items in this relationship"
        );
        $this->assertGreaterThan(0, $factory->getId(TestDataObject::class, "testobject2"));
        $object2 = DataObject::get_by_id(
            TestDataObject::class,
            $factory->getId(TestDataObject::class, "testobject2")
        );
        $this->assertTrue(
            $object2->ManyManyRelation()->Count() == 1,
            "Should be one item in this relationship"
        );
    }

    public function testWriteInto()
    {
        $factory = Injector::inst()->create(FixtureFactory::class);

        $absPath = __DIR__ . '/YamlFixtureTest.yml';
        $fixture = Injector::inst()->create(YamlFixture::class, $absPath);
        $fixture->writeInto($factory);

        $this->assertGreaterThan(
            0,
            $factory->getId(TestDataObject::class, "testobject1")
        );
    }
}
