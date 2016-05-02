<?php
/**
 * @package framework
 * @subpackage tests
 */
class DecimalTest extends SapphireTest {

	protected static $fixture_file = 'DecimalTest.yml';

	protected $testDataObject;

	protected $extraDataObjects = array(
		'DecimalTest_DataObject'
	);

	public function setUp() {
		parent::setUp();
		$this->testDataObject = $this->objFromFixture('DecimalTest_DataObject', 'test-dataobject');
	}

	public function testDefaultValue() {
		$this->assertEquals($this->testDataObject->MyDecimal1, 0,
			'Database default for Decimal type is 0');
	}

	public function testSpecifiedDefaultValue() {
		$this->assertEquals($this->testDataObject->MyDecimal2, 2.5,
			'Default value for Decimal type is set to 2.5');
	}

	public function testInvalidSpecifiedDefaultValue() {
		$this->assertEquals($this->testDataObject->MyDecimal3, 0,
			'Invalid default value for Decimal type is casted to 0');
	}

	public function testSpecifiedDefaultValueInDefaultsArray() {
		$this->assertEquals($this->testDataObject->MyDecimal4, 4,
			'Default value for Decimal type is set to 4');
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class DecimalTest_DataObject extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar',
		'MyDecimal1' => 'Decimal',
		'MyDecimal2' => 'Decimal(5,3,2.5)',
		'MyDecimal3' => 'Decimal(4,2,"Invalid default value")',
		'MyDecimal4' => 'Decimal'
	);

	private static $defaults = array(
		'MyDecimal4' => 4
	);

}
