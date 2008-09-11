<?php

class DBFieldTest extends SapphireTest {
	
	/**
	 * Test the prepValueForDB() method on DBField.
	 */
	function testPrepValueForDB() {
		
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
		$this->assertEquals('0', singleton('Boolean')->prepValueForDB(0));
		$this->assertEquals('0', singleton('Boolean')->prepValueForDB(null));
		$this->assertEquals('0', singleton('Boolean')->prepValueForDB(false));
		$this->assertEquals('0', singleton('Boolean')->prepValueForDB(''));
		$this->assertEquals('0', singleton('Boolean')->prepValueForDB('0'));
		
		/* Boolean behaviour, asserting we have 1 */
		$this->assertEquals('1', singleton('Boolean')->prepValueForDB(true));
		$this->assertEquals('1', singleton('Boolean')->prepValueForDB(1));
		$this->assertEquals('1', singleton('Boolean')->prepValueForDB('1'));
		
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
	}
	
}

?>