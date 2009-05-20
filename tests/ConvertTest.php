<?php
/**
 * Test various functions on the {@link Convert} class.
 * @package sapphire
 * @subpackage tests
 */
class ConvertTest extends SapphireTest {

	/**
	 * Tests {@link Convert::raw2att()}
	 */
	function testRaw2Att() {
		$val1 = '<input type="text">';
		$this->assertEquals('&lt;input type=&quot;text&quot;&gt;', Convert::raw2att($val1), 'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('This is some normal text.', Convert::raw2att($val2), 'Normal text is not escaped');
	}
	
	/**
	 * Tests {@link Convert::raw2htmlatt()}
	 */
	function testRaw2HtmlAtt() {
		$val1 = '<input type="text">';
		$this->assertEquals('ltinputtypequottextquotgt', Convert::raw2htmlatt($val1), 'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('Thisissomenormaltext', Convert::raw2htmlatt($val2), 'Normal text is not escaped');
	}
	
	/**
	 * Tests {@link Convert::raw2xml()}
	 */
	function testRaw2Xml() {
		$val1 = '<input type="text">';
		$this->assertEquals('&lt;input type=&quot;text&quot;&gt;', Convert::raw2xml($val1), 'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('This is some normal text.', Convert::raw2xml($val2), 'Normal text is not escaped');
	}
	
	/**
	 * Tests {@link Convert::xml2raw()}
	 */
	function testXml2Raw() {
		$val1 = '&lt;input type=&quot;text&quot;&gt;';
		$this->assertEquals('<input type="text">', Convert::xml2raw($val1), 'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('This is some normal text.', Convert::xml2raw($val2), 'Normal text is not escaped');
	}
	
}