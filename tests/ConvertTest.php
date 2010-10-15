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
	
	function testHtml2raw() {
		$val1 = 'This has a <strong>strong tag</strong>.'; 
		$this->assertEquals('This has a *strong tag*.', Convert::xml2raw($val1), 'Strong tags are replaced with asterisks');
		
		$val1 = 'This has a <b class="test" style="font-weight: bold">b tag with attributes</b>.'; 
		$this->assertEquals('This has a *b tag with attributes*.', Convert::xml2raw($val1), 'B tags with attributes are replaced with asterisks');
		
		$val2 = 'This has a <strong class="test" style="font-weight: bold">strong tag with attributes</STRONG>.'; 
		$this->assertEquals('This has a *strong tag with attributes*.', Convert::xml2raw($val2), 'Strong tags with attributes are replaced with asterisks');
	
		
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

	function testArray2JSON() {
		$val = array(
			'Joe' => 'Bloggs',
			'Tom' => 'Jones',
			'My' => array(
				'Complicated' => 'Structure'
			)
		);
		$encoded = Convert::array2json($val);
		$this->assertEquals('{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}', $encoded, 'Array is encoded in JSON');
	}

	function testJSON2Array() {
		$val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
		$decoded = Convert::json2array($val);
		$this->assertEquals(3, count($decoded), '3 items in the decoded array');
		$this->assertContains('Bloggs', $decoded, 'Contains "Bloggs" value in decoded array');
		$this->assertContains('Jones', $decoded, 'Contains "Jones" value in decoded array');
	}

	function testJSON2Obj() {
		$val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
		$obj = Convert::json2obj($val);
		$this->assertEquals('Bloggs', $obj->Joe);
		$this->assertEquals('Jones', $obj->Tom);
		$this->assertEquals('Structure', $obj->My->Complicated);
	}

}