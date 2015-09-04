<?php
/**
 * @package framework
 * @subpackage tests
 */
class CompositeDBFieldTest extends SapphireTest {

	protected $extraDataObjects = array(
		'CompositeDBFieldTest_DataObject',
		'SubclassedDBFieldObject',
	);

	public function testHasDatabaseFieldOnDataObject() {
		$obj = singleton('CompositeDBFieldTest_DataObject');

		$this->assertTrue($obj->hasDatabaseField('MyMoneyAmount'));
		$this->assertTrue($obj->hasDatabaseField('MyMoneyCurrency'));
		$this->assertFalse($obj->hasDatabaseField('MyMoney'));
	}

	/**
	 * Test DataObject::composite_fields() and DataObject::is_composite_field()
	 */
	public function testCompositeFieldMetaDataFunctions() {
		$this->assertEquals('Money', DataObject::is_composite_field('CompositeDBFieldTest_DataObject', 'MyMoney'));
		$this->assertNull(DataObject::is_composite_field('CompositeDBFieldTest_DataObject', 'Title'));
		$this->assertEquals(array('MyMoney' => 'Money'),
			DataObject::composite_fields('CompositeDBFieldTest_DataObject'));


		$this->assertEquals('Money', DataObject::is_composite_field('SubclassedDBFieldObject', 'MyMoney'));
		$this->assertEquals('Money', DataObject::is_composite_field('SubclassedDBFieldObject', 'OtherMoney'));
		$this->assertNull(DataObject::is_composite_field('SubclassedDBFieldObject', 'Title'));
		$this->assertNull(DataObject::is_composite_field('SubclassedDBFieldObject', 'OtherField'));
				$this->assertEquals(array('MyMoney' => 'Money', 'OtherMoney' => 'Money'),
			DataObject::composite_fields('SubclassedDBFieldObject'));
	}

	/**
	 * Tests that changes to the fields affect the underlying dataobject, and vice versa
	 */
	public function testFieldBinding() {
		$object = new CompositeDBFieldTest_DataObject();
		$object->MyMoney->Currency = 'NZD';
		$object->MyMoney->Amount = 100.0;
		$this->assertEquals('NZD', $object->MyMoneyCurrency);
		$this->assertEquals(100.0, $object->MyMoneyAmount);
		$object->write();

		$object2 = CompositeDBFieldTest_DataObject::get()->byID($object->ID);
		$this->assertEquals('NZD', $object2->MyMoney->Currency);
		$this->assertEquals(100.0, $object2->MyMoney->Amount);

		$object2->MyMoneyCurrency = 'USD';
		$this->assertEquals('USD', $object2->MyMoney->Currency);

		$object2->MyMoney->setValue(array('Currency' => 'EUR', 'Amount' => 200.0));
		$this->assertEquals('EUR', $object2->MyMoneyCurrency);
		$this->assertEquals(200.0, $object2->MyMoneyAmount);
	}
}

class CompositeDBFieldTest_DataObject extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Text',
		'MyMoney' => 'Money',
	);
}

class SubclassedDBFieldObject extends CompositeDBFieldTest_DataObject {
	private static $db = array(
		'OtherField' => 'Text',
		'OtherMoney' => 'Money',
	);
}
