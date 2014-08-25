<?php
/**
 * Test various functions on the {@link Convert} class.
 * @package framework
 * @subpackage tests
 */
class ConvertTest extends SapphireTest {

	/**
	 * Tests {@link Convert::raw2att()}
	 */
	public function testRaw2Att() {
		$val1 = '<input type="text">';
		$this->assertEquals('&lt;input type=&quot;text&quot;&gt;', Convert::raw2att($val1),
			'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('This is some normal text.', Convert::raw2att($val2),
			'Normal text is not escaped');
	}
	
	/**
	 * Tests {@link Convert::raw2htmlatt()}
	 */
	public function testRaw2HtmlAtt() {
		$val1 = '<input type="text">';
		$this->assertEquals('&lt;input type=&quot;text&quot;&gt;', Convert::raw2htmlatt($val1),
			'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('This is some normal text.', Convert::raw2htmlatt($val2),
			'Normal text is not escaped');
	}
	
	public function testHtml2raw() {
		$val1 = 'This has a <strong>strong tag</strong>.'; 
		$this->assertEquals('This has a *strong tag*.', Convert::xml2raw($val1),
			'Strong tags are replaced with asterisks');
		
		$val1 = 'This has a <b class="test" style="font-weight: bold">b tag with attributes</b>.'; 
		$this->assertEquals('This has a *b tag with attributes*.', Convert::xml2raw($val1),
			'B tags with attributes are replaced with asterisks');
		
		$val2 = 'This has a <strong class="test" style="font-weight: bold">strong tag with attributes</STRONG>.'; 
		$this->assertEquals('This has a *strong tag with attributes*.', Convert::xml2raw($val2),
			'Strong tags with attributes are replaced with asterisks');
		
		$val3 = '<script type="text/javascript">Some really nasty javascript here</script>';
		$this->assertEquals('', Convert::xml2raw($val3),
			'Script tags are completely removed');
		
		$val4 = '<style type="text/css">Some really nasty CSS here</style>';
		$this->assertEquals('', Convert::xml2raw($val4),
			'Style tags are completely removed');
		
		$val5 = '<script type="text/javascript">Some really nasty
		multiline javascript here</script>';
		$this->assertEquals('', Convert::xml2raw($val5),
			'Multiline script tags are completely removed');
		
		$val6 = '<style type="text/css">Some really nasty
		multiline CSS here</style>';
		$this->assertEquals('', Convert::xml2raw($val6),
			'Multiline style tags are completely removed');
	}
	
	/**
	 * Tests {@link Convert::raw2xml()}
	 */
	public function testRaw2Xml() {
		$val1 = '<input type="text">';
		$this->assertEquals('&lt;input type=&quot;text&quot;&gt;', Convert::raw2xml($val1),
			'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('This is some normal text.', Convert::raw2xml($val2),
			'Normal text is not escaped');

		$val3 = "This is test\nNow on a new line.";
		$this->assertEquals("This is test\nNow on a new line.", Convert::raw2xml($val3),
			'Newlines are retained. They should not be replaced with <br /> as it is not XML valid');
	}
	
	public function testRaw2HtmlName() {
		$val1 = 'test test 123';
		$this->assertEquals('testtest123', Convert::raw2htmlname($val1));
	}
	
	/**
	 * Tests {@link Convert::xml2raw()}
	 */
	public function testXml2Raw() {
		$val1 = '&lt;input type=&quot;text&quot;&gt;';
		$this->assertEquals('<input type="text">', Convert::xml2raw($val1), 'Special characters are escaped');
		
		$val2 = 'This is some normal text.';
		$this->assertEquals('This is some normal text.', Convert::xml2raw($val2), 'Normal text is not escaped');
	}

	public function testArray2JSON() {
		$val = array(
			'Joe' => 'Bloggs',
			'Tom' => 'Jones',
			'My' => array(
				'Complicated' => 'Structure'
			)
		);
		$encoded = Convert::array2json($val);
		$this->assertEquals('{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}', $encoded,
			'Array is encoded in JSON');
	}

	public function testJSON2Array() {
		$val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
		$decoded = Convert::json2array($val);
		$this->assertEquals(3, count($decoded), '3 items in the decoded array');
		$this->assertContains('Bloggs', $decoded, 'Contains "Bloggs" value in decoded array');
		$this->assertContains('Jones', $decoded, 'Contains "Jones" value in decoded array');
		$this->assertContains('Structure', $decoded['My']['Complicated']);
	}

	public function testJSON2Obj() {
		$val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
		$obj = Convert::json2obj($val);
		$this->assertEquals('Bloggs', $obj->Joe);
		$this->assertEquals('Jones', $obj->Tom);
		$this->assertEquals('Structure', $obj->My->Complicated);
	}
	
	/**
	 * @todo test toASCII()
	 */
	public function testRaw2URL() {
		$orig = Config::inst()->get('URLSegmentFilter', 'default_allow_multibyte');
		Config::inst()->update('URLSegmentFilter', 'default_allow_multibyte', false);
		$this->assertEquals('foo', Convert::raw2url('foo'));
		$this->assertEquals('foo-and-bar', Convert::raw2url('foo & bar'));
		$this->assertEquals('foo-and-bar', Convert::raw2url('foo &amp; bar!'));
		$this->assertEquals('foos-bar-2', Convert::raw2url('foo\'s [bar] (2)'));
		Config::inst()->update('URLSegmentFilter', 'default_allow_multibyte', $orig);
	}
	
	/**
	 * Helper function for comparing characters with significant whitespaces
	 * @param type $expected
	 * @param type $actual 
	 */
	protected function assertEqualsQuoted($expected, $actual) {
		$message = sprintf(
			"Expected \"%s\" but given \"%s\"", 
			addcslashes($expected, "\r\n"), 
			addcslashes($actual, "\r\n")
		);
		$this->assertEquals($expected, $actual, $message);
	}
	
	public function testNL2OS() {
		
		foreach(array("\r\n", "\r", "\n") as $nl) {
			
			// Base case: no action
			$this->assertEqualsQuoted(
				"Base case",
				Convert::nl2os("Base case", $nl)
			);
			
			// Mixed formats
			$this->assertEqualsQuoted(
				"Test{$nl}Text{$nl}Is{$nl}{$nl}Here{$nl}.",
				Convert::nl2os("Test\rText\r\nIs\n\rHere\r\n.", $nl)
			);
			
			// Test that multiple runs are non-destructive
			$expected = "Test{$nl}Text{$nl}Is{$nl}{$nl}Here{$nl}.";
			$this->assertEqualsQuoted(
				$expected,
				Convert::nl2os($expected, $nl)
			);
			
			// Check repeated sequence behaves correctly
			$expected = "{$nl}{$nl}{$nl}{$nl}{$nl}{$nl}{$nl}{$nl}";
			$input = "\r\r\n\r\r\n\n\n\n\r";
			$this->assertEqualsQuoted(
				$expected,
				Convert::nl2os($input, $nl)
			);
		}
	}
	
	public function testRaw2JS() {
		// Test attempt to break out of string
		$this->assertEquals(
			'\\"; window.location=\\"http://www.google.com',
			Convert::raw2js('"; window.location="http://www.google.com')
		);
		$this->assertEquals(
			'\\\'; window.location=\\\'http://www.google.com',
			Convert::raw2js('\'; window.location=\'http://www.google.com')
		);
		// Test attempt to close script tag
		$this->assertEquals(
			'\\"; \\x3c/script\\x3e\\x3ch1\\x3eHa \\x26amp; Ha\\x3c/h1\\x3e\\x3cscript\\x3e',
			Convert::raw2js('"; </script><h1>Ha &amp; Ha</h1><script>')
		);
		// Test newlines are properly escaped
		$this->assertEquals(
			'New\\nLine\\rReturn', Convert::raw2js("New\nLine\rReturn")
		);
		// Check escape of slashes
		$this->assertEquals(
			'\\\\\\"\\x3eClick here',
			Convert::raw2js('\\">Click here')
		);
	}
	
	public function testRaw2JSON() {
		
		// Test object
		$input = new stdClass();
		$input->Title = 'My Object';
		$input->Content = '<p>Data</p>';
		$this->assertEquals(
			'{"Title":"My Object","Content":"<p>Data<\/p>"}',
			Convert::raw2json($input)
		);
		
		// Array
		$array = array('One' => 'Apple', 'Two' => 'Banana');
		$this->assertEquals(
			'{"One":"Apple","Two":"Banana"}',
			Convert::raw2json($array)
		);
		
		// String value with already encoded data. Result should be quoted.
		$value = '{"Left": "Value"}';
		$this->assertEquals(
			'"{\\"Left\\": \\"Value\\"}"',
			Convert::raw2json($value)
		);
	}
}
