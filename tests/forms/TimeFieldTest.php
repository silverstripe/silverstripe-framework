<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class TimeFieldTest extends SapphireTest {
	function testDataValue12h() {
		$dateField12h = new TimeField('Time', 'Time');
		
		$dateField12h->setValue('11pm');
		$this->assertEquals($dateField12h->dataValue(), '11:00pm');
				
		$dateField12h->setValue('23:59');
		$this->assertEquals($dateField12h->dataValue(), '11:59pm');
		
		$dateField12h->setValue('11:59pm');
		$this->assertEquals($dateField12h->dataValue(), '11:59pm');
	}
	
	function testDataValue24h() {
		$dateField24h = new TimeField('Time', 'Time', null, 'H:i');
		
		$dateField24h->setValue('11pm');
		$this->assertEquals($dateField24h->dataValue(), '23:00');
		
		$dateField24h->setValue('23:59');
		$this->assertEquals($dateField24h->dataValue(), '23:59');
		
		$dateField24h->setValue('11:59pm');
		$this->assertEquals($dateField24h->dataValue(), '23:59');
	}
}