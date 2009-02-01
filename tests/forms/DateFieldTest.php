<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DateFieldTest extends SapphireTest {
	
	function testDMYFormat() {
		/* We get YYYY-MM-DD format as the data value for DD/MM/YYYY input value */
		$dateField = new DateField('Date', 'Date', '04/03/2003');
		$this->assertEquals($dateField->dataValue(), '2003-03-04');
		
		/* Even if value hasn't got leading 0's in it we still get the correct data value */
		$dateField2 = new DateField('Date', 'Date', '4/3/03');
		$this->assertEquals($dateField2->dataValue(), '03-3-4');
	}
	
	function testYMDFormat() {
		/* We get YYYY-MM-DD format as the data value for YYYY-MM-DD input value */
		$dateField = new DateField('Date', 'Date', '2003/03/04');
		$this->assertEquals($dateField->dataValue(), '2003-03-04');
		
		/* Even if input value hasn't got leading 0's in it we still get the correct data value */
		$dateField2 = new DateField('Date', 'Date', '2003/3/4');
		$this->assertEquals($dateField2->dataValue(), '2003-03-04');
	}
	
	function testMDYFormat() {
		/* We get MM-DD-YYYY format as the data value for YYYY-MM-DD input value */
		$dateField = new DateField('Date', 'Date', '03/04/2003');
		$this->assertEquals($dateField->dataValue(), '2003-04-03');
		
		/* Even if input value hasn't got leading 0's in it we still get the correct data value */
		$dateField2 = new DateField('Date', 'Date', '3/4/03');
		$this->assertEquals($dateField2->dataValue(), '03-4-3');
	}
	
}
?>