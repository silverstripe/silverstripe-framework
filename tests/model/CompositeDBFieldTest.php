<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class CompositeDBFieldTest extends SapphireTest {
	function testHasDatabaseFieldOnDataObject() {
		$obj = singleton('CompositeDBFieldTest_DataObject');
		
		$this->assertTrue($obj->hasDatabaseField('MyMoneyAmount'));
		$this->assertTrue($obj->hasDatabaseField('MyMoneyCurrency'));
		$this->assertFalse($obj->hasDatabaseField('MyMoney'));
	}
}

class CompositeDBFieldTest_DataObject extends DataObject implements TestOnly {
	static $db = array(
		'Title' => 'Text', 
		'MyMoney' => 'Money', 
	);
}
?>