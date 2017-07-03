<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;

/**
 * @skipUpgrade
 */
class DBCompositeTest extends SapphireTest
{

    protected static $extra_dataobjects = array(
        DBCompositeTest\TestObject::class,
        DBCompositeTest\SubclassedDBFieldObject::class,
    );

    public function testHasDatabaseFieldOnDataObject()
    {
        $obj = singleton(DBCompositeTest\TestObject::class);

        $this->assertTrue($obj->hasDatabaseField('MyMoneyAmount'));
        $this->assertTrue($obj->hasDatabaseField('MyMoneyCurrency'));
        $this->assertFalse($obj->hasDatabaseField('MyMoney'));

        // Check that nested fields are exposed properly
        $this->assertTrue($obj->dbObject('MyMoney')->hasField('Amount'));
        $this->assertTrue($obj->dbObject('MyMoney')->hasField('Currency'));

        // Test getField accessor
        $this->assertTrue($obj->MyMoney instanceof DBMoney);
        $this->assertTrue($obj->MyMoney->hasField('Amount'));
        $obj->MyMoney->Amount = 100.00;
        $this->assertEquals(100.00, $obj->MyMoney->Amount);
        $this->assertEquals(100.00, $obj->MyMoneyAmount);

        // Not strictly correct
        $this->assertFalse($obj->dbObject('MyMoney')->hasField('MyMoneyAmount'));
        $this->assertFalse($obj->dbObject('MyMoney')->hasField('MyMoneyCurrency'));
        $this->assertFalse($obj->dbObject('MyMoney')->hasField('MyMoney'));
    }

    /**
     * Test DataObject::composite_fields() and DataObject::is_composite_field()
     */
    public function testCompositeFieldMetaDataFunctions()
    {
        $schema = DataObject::getSchema();
        $this->assertEquals('Money', $schema->compositeField(DBCompositeTest\TestObject::class, 'MyMoney'));
        $this->assertNull($schema->compositeField(DBCompositeTest\TestObject::class, 'Title'));
        $this->assertEquals(
            array(
                'MyMoney' => 'Money',
                'OverriddenMoney' => 'Money'
            ),
            $schema->compositeFields(DBCompositeTest\TestObject::class)
        );


        $this->assertEquals('Money', $schema->compositeField(DBCompositeTest\SubclassedDBFieldObject::class, 'MyMoney'));
        $this->assertEquals('Money', $schema->compositeField(DBCompositeTest\SubclassedDBFieldObject::class, 'OtherMoney'));
        $this->assertNull($schema->compositeField(DBCompositeTest\SubclassedDBFieldObject::class, 'Title'));
        $this->assertNull($schema->compositeField(DBCompositeTest\SubclassedDBFieldObject::class, 'OtherField'));
        $this->assertEquals(
            array(
                'MyMoney' => 'Money',
                'OtherMoney' => 'Money',
                'OverriddenMoney' => 'Money',
            ),
            $schema->compositeFields(DBCompositeTest\SubclassedDBFieldObject::class)
        );
    }

    /**
     * Tests that changes to the fields affect the underlying dataobject, and vice versa
     */
    public function testFieldBinding()
    {
        $object = new DBCompositeTest\TestObject();
        $object->MyMoney->Currency = 'NZD';
        $object->MyMoney->Amount = 100.0;
        $this->assertEquals('NZD', $object->MyMoneyCurrency);
        $this->assertEquals(100.0, $object->MyMoneyAmount);
        $object->write();

        $object2 = DBCompositeTest\TestObject::get()->byID($object->ID);
        $this->assertEquals('NZD', $object2->MyMoney->Currency);
        $this->assertEquals(100.0, $object2->MyMoney->Amount);

        $object2->MyMoneyCurrency = 'USD';
        $this->assertEquals('USD', $object2->MyMoney->Currency);

        $object2->MyMoney->setValue(array('Currency' => 'EUR', 'Amount' => 200.0));
        $this->assertEquals('EUR', $object2->MyMoneyCurrency);
        $this->assertEquals(200.0, $object2->MyMoneyAmount);
    }

    /**
     * Ensures that composite fields are assigned to the correct tables
     */
    public function testInheritedTables()
    {
        $object1 = new DBCompositeTest\TestObject();
        $object2 = new DBCompositeTest\SubclassedDBFieldObject();

        $this->assertEquals('DBCompositeTest_DataObject', $object1->dbObject('MyMoney')->getTable());
        $this->assertEquals('DBCompositeTest_DataObject', $object1->dbObject('OverriddenMoney')->getTable());
        $this->assertEquals('DBCompositeTest_DataObject', $object2->dbObject('MyMoney')->getTable());
        $this->assertEquals('DBCompositeTest_SubclassedDBFieldObject', $object2->dbObject('OtherMoney')->getTable());
        $this->assertEquals('DBCompositeTest_SubclassedDBFieldObject', $object2->dbObject('OverriddenMoney')->getTable());
    }
}
