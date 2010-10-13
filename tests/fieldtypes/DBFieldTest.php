<?php

/**
 * 
 * Tests for DBField objects.
 * @package sapphire
 * @subpackage tests
 *
 */
class DBFieldTest extends SapphireTest {
	
	/**
	 * Test the nullValue() method on DBField.
	 */
	function testNullValue() {
		/* Float and Double use 0 for "null" value representation */
		$this->assertEquals(0, singleton('Float')->nullValue());
		$this->assertEquals(0, singleton('Double')->nullValue());
	}
	
	/**
	 * Test the prepValueForDB() method on DBField.
	 */
	function testPrepValueForDB() {
		
		/* Float behaviour, asserting we have 0 */
		$this->assertEquals('0', singleton('Float')->prepValueForDB(0));
		$this->assertEquals('0', singleton('Float')->prepValueForDB(null));
		$this->assertEquals('0', singleton('Float')->prepValueForDB(false));
		$this->assertEquals('0', singleton('Float')->prepValueForDB(''));
		$this->assertEquals('0', singleton('Float')->prepValueForDB('0'));

		/* Double behaviour, asserting we have 0 */
		$this->assertEquals('0', singleton('Double')->prepValueForDB(0));
		$this->assertEquals('0', singleton('Double')->prepValueForDB(null));
		$this->assertEquals('0', singleton('Double')->prepValueForDB(false));
		$this->assertEquals('0', singleton('Double')->prepValueForDB(''));
		$this->assertEquals('0', singleton('Double')->prepValueForDB('0'));

		/* Integer behaviour, asserting we have 0 */
		$this->assertEquals('0', singleton('Int')->prepValueForDB(0));
		$this->assertEquals('0', singleton('Int')->prepValueForDB(null));
		$this->assertEquals('0', singleton('Int')->prepValueForDB(false));
		$this->assertEquals('0', singleton('Int')->prepValueForDB(''));
		$this->assertEquals('0', singleton('Int')->prepValueForDB('0'));
		
		/* Integer behaviour, asserting we have 1 */
		$this->assertEquals('1', singleton('Int')->prepValueForDB(true));
		$this->assertEquals('1', singleton('Int')->prepValueForDB(1));
		$this->assertEquals('1', singleton('Int')->prepValueForDB('1'));

		/* Decimal behaviour, asserting we have 0 */
		$this->assertEquals('0', singleton('Decimal')->prepValueForDB(0));
		$this->assertEquals('0', singleton('Decimal')->prepValueForDB(null));
		$this->assertEquals('0', singleton('Decimal')->prepValueForDB(false));
		$this->assertEquals('0', singleton('Decimal')->prepValueForDB(''));
		$this->assertEquals('0', singleton('Decimal')->prepValueForDB('0'));

		/* Decimal behaviour, asserting we have 1 */
		$this->assertEquals('1', singleton('Decimal')->prepValueForDB(true));
		$this->assertEquals('1', singleton('Decimal')->prepValueForDB(1));
		$this->assertEquals('1', singleton('Decimal')->prepValueForDB('1'));

		/* Boolean behaviour, asserting we have 0 */
		$this->assertEquals("'0'", singleton('Boolean')->prepValueForDB(0));
		$this->assertEquals("'0'", singleton('Boolean')->prepValueForDB(null));
		$this->assertEquals("'0'", singleton('Boolean')->prepValueForDB(false));
		$this->assertEquals("'0'", singleton('Boolean')->prepValueForDB(''));
		$this->assertEquals("'0'", singleton('Boolean')->prepValueForDB('0'));
		
		/* Boolean behaviour, asserting we have 1 */
		$this->assertEquals("'1'", singleton('Boolean')->prepValueForDB(true));
		$this->assertEquals("'1'", singleton('Boolean')->prepValueForDB(1));
		$this->assertEquals("'1'", singleton('Boolean')->prepValueForDB('1'));
		
		/* Varchar behaviour */
		$this->assertEquals("'0'", singleton('Varchar')->prepValueForDB(0));
		$this->assertEquals("null", singleton('Varchar')->prepValueForDB(null));
		$this->assertEquals("null", singleton('Varchar')->prepValueForDB(false));
		$this->assertEquals("null", singleton('Varchar')->prepValueForDB(''));
		$this->assertEquals("'0'", singleton('Varchar')->prepValueForDB('0'));
		$this->assertEquals("'1'", singleton('Varchar')->prepValueForDB(1));
		$this->assertEquals("'1'", singleton('Varchar')->prepValueForDB(true));
		$this->assertEquals("'1'", singleton('Varchar')->prepValueForDB('1'));
		$this->assertEquals("'00000'", singleton('Varchar')->prepValueForDB('00000'));
		$this->assertEquals("'0'", singleton('Varchar')->prepValueForDB(0000));
		$this->assertEquals("'test'", singleton('Varchar')->prepValueForDB('test'));
		$this->assertEquals("'123'", singleton('Varchar')->prepValueForDB(123));

		/* AllowEmpty Varchar behaviour */
		$varcharField = new Varchar("testfield", 50, array("nullifyEmpty"=>false));
		$this->assertSame("'0'", $varcharField->prepValueForDB(0));
		$this->assertSame("null", $varcharField->prepValueForDB(null));
		$this->assertSame("null", $varcharField->prepValueForDB(false));
		$this->assertSame("''", $varcharField->prepValueForDB(''));
		$this->assertSame("'0'", $varcharField->prepValueForDB('0'));
		$this->assertSame("'1'", $varcharField->prepValueForDB(1));
		$this->assertSame("'1'", $varcharField->prepValueForDB(true));
		$this->assertSame("'1'", $varcharField->prepValueForDB('1'));
		$this->assertSame("'00000'", $varcharField->prepValueForDB('00000'));
		$this->assertSame("'0'", $varcharField->prepValueForDB(0000));
		$this->assertSame("'test'", $varcharField->prepValueForDB('test'));
		$this->assertSame("'123'", $varcharField->prepValueForDB(123));
		unset($varcharField);
		
		/* Text behaviour */
		$this->assertEquals("'0'", singleton('Text')->prepValueForDB(0));
		$this->assertEquals("null", singleton('Text')->prepValueForDB(null));
		$this->assertEquals("null", singleton('Text')->prepValueForDB(false));
		$this->assertEquals("null", singleton('Text')->prepValueForDB(''));
		$this->assertEquals("'0'", singleton('Text')->prepValueForDB('0'));
		$this->assertEquals("'1'", singleton('Text')->prepValueForDB(1));
		$this->assertEquals("'1'", singleton('Text')->prepValueForDB(true));
		$this->assertEquals("'1'", singleton('Text')->prepValueForDB('1'));
		$this->assertEquals("'00000'", singleton('Text')->prepValueForDB('00000'));
		$this->assertEquals("'0'", singleton('Text')->prepValueForDB(0000));
		$this->assertEquals("'test'", singleton('Text')->prepValueForDB('test'));
		$this->assertEquals("'123'", singleton('Text')->prepValueForDB(123));

		/* AllowEmpty Text behaviour */
		$textField = new Text("testfield", array("nullifyEmpty"=>false));
		$this->assertSame("'0'", $textField->prepValueForDB(0));
		$this->assertSame("null", $textField->prepValueForDB(null));
		$this->assertSame("null", $textField->prepValueForDB(false));
		$this->assertSame("''", $textField->prepValueForDB(''));
		$this->assertSame("'0'", $textField->prepValueForDB('0'));
		$this->assertSame("'1'", $textField->prepValueForDB(1));
		$this->assertSame("'1'", $textField->prepValueForDB(true));
		$this->assertSame("'1'", $textField->prepValueForDB('1'));
		$this->assertSame("'00000'", $textField->prepValueForDB('00000'));
 		$this->assertSame("'0'", $textField->prepValueForDB(0000));
		$this->assertSame("'test'", $textField->prepValueForDB('test'));
		$this->assertSame("'123'", $textField->prepValueForDB(123));
		unset($textField);
		
		/* Time behaviour */
		$time = singleton('Time');
		$time->setValue('00:01am');
		$this->assertEquals("00:01:00", $time->getValue());
		$time->setValue('00:59am');
		$this->assertEquals("00:59:00", $time->getValue());
		$time->setValue('11:59am');
		$this->assertEquals("11:59:00", $time->getValue());
		$time->setValue('12:00pm');
		$this->assertEquals("12:00:00", $time->getValue());
		$time->setValue('12:59am');
		$this->assertEquals("12:59:00", $time->getValue());
		$time->setValue('1:00pm');
		$this->assertEquals("13:00:00", $time->getValue());
		$time->setValue('11:59pm');
		$this->assertEquals("23:59:00", $time->getValue());
		$time->setValue('00:00am');
		$this->assertEquals("00:00:00", $time->getValue());
		$time->setValue('00:00:00');
		$this->assertEquals("00:00:00", $time->getValue());
	}
	
	function testHasValue() {
		$varcharField = new Varchar("testfield");
		$this->assertTrue($varcharField->getNullifyEmpty());
		$varcharField->setValue('abc');
		$this->assertTrue($varcharField->hasValue());
		$varcharField->setValue('');
		$this->assertFalse($varcharField->hasValue());
		$varcharField->setValue(null);
		$this->assertFalse($varcharField->hasValue());
		
		$varcharField = new Varchar("testfield", 50, array('nullifyEmpty'=>false));
		$this->assertFalse($varcharField->getNullifyEmpty());
		$varcharField->setValue('abc');
		$this->assertTrue($varcharField->hasValue());
		$varcharField->setValue('');
		$this->assertTrue($varcharField->hasValue());
		$varcharField->setValue(null);
		$this->assertFalse($varcharField->hasValue());

		$textField = new Text("testfield");
		$this->assertTrue($textField->getNullifyEmpty());
		$textField->setValue('abc');
		$this->assertTrue($textField->hasValue());
		$textField->setValue('');
		$this->assertFalse($textField->hasValue());
		$textField->setValue(null);
		$this->assertFalse($textField->hasValue());
		
		$textField = new Text("testfield", array('nullifyEmpty'=>false));
		$this->assertFalse($textField->getNullifyEmpty());
		$textField->setValue('abc');
		$this->assertTrue($textField->hasValue());
		$textField->setValue('');
		$this->assertTrue($textField->hasValue());
		$textField->setValue(null);
		$this->assertFalse($textField->hasValue());
	}
}

?>
