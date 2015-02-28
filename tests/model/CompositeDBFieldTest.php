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
