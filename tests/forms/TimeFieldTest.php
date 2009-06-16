<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class TimeFieldTest extends SapphireTest {
	function testDataValue12h() {
		$field12h = new TimeField('Time', 'Time');
		
		$field12h->setValue('11pm');
		$this->assertEquals($field12h->dataValue(), '11:00pm');
				
		$field12h->setValue('23:59');
		$this->assertEquals($field12h->dataValue(), '11:59pm');
		
		$field12h->setValue('11:59pm');
		$this->assertEquals($field12h->dataValue(), '11:59pm');
		
		$field12h->setValue('11:59 pm');
		$this->assertEquals($field12h->dataValue(), '11:59pm');
	}
	
	function testDataValue24h() {
		$field24h = new TimeField('Time', 'Time', null, 'H:i');
		
		$field24h->setValue('11pm');
		$this->assertEquals($field24h->dataValue(), '23:00');
		
		$field24h->setValue('23:59');
		$this->assertEquals($field24h->dataValue(), '23:59');
		
		$field24h->setValue('11:59pm');
		$this->assertEquals($field24h->dataValue(), '23:59');
		
		$field24h->setValue('11:59 pm');
		$this->assertEquals($field24h->dataValue(), '23:59');
	}
	
	function testOverrideWithNull() {
		$field = new TimeField('Time', 'Time');
		
		$field->setValue('11:00pm');
		$field->setValue('');
		$this->assertEquals($field->dataValue(), '');
	}
}