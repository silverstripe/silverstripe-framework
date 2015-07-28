<?php

/**
 * Test various functions on the {@link Convert} class.
 *
 * @package framework
 * @subpackage tests
 */
class ConvertTest extends SapphireTest {

	protected $usesDatabase = false;

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

	/**
	 * Tests {@link Convert::html2raw()}
	 */
	public function testHtml2raw() {
		$val1 = 'This has a <strong>strong tag</strong>.';
		$this->assertEquals('This has a *strong tag*.', Convert::html2raw($val1),
			'Strong tags are replaced with asterisks');

		$val1 = 'This has a <b class="test" style="font-weight: bold">b tag with attributes</b>.';
		$this->assertEquals('This has a *b tag with attributes*.', Convert::html2raw($val1),
			'B tags with attributes are replaced with asterisks');

		$val2 = 'This has a <strong class="test" style="font-weight: bold">strong tag with attributes</STRONG>.';
		$this->assertEquals('This has a *strong tag with attributes*.', Convert::html2raw($val2),
			'Strong tags with attributes are replaced with asterisks');

		$val3 = '<script type="text/javascript">Some really nasty javascript here</script>';
		$this->assertEquals('', Convert::html2raw($val3),
			'Script tags are completely removed');

		$val4 = '<style type="text/css">Some really nasty CSS here</style>';
		$this->assertEquals('', Convert::html2raw($val4),
			'Style tags are completely removed');

		$val5 = '<script type="text/javascript">Some really nasty
		multiline javascript here</script>';
		$this->assertEquals('', Convert::html2raw($val5),
			'Multiline script tags are completely removed');

		$val6 = '<style type="text/css">Some really nasty
		multiline CSS here</style>';
		$this->assertEquals('', Convert::html2raw($val6),
			'Multiline style tags are completely removed');

		$val7 = '<p>That&#39;s absolutely correct</p>';
		$this->assertEquals(
			"That's absolutely correct",
			Convert::html2raw($val7),
			"Single quotes are decoded correctly"
		);

		$val8 = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor '.
				'incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud '.
				'exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute '.
				'irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla '.
				'pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia '.
				'deserunt mollit anim id est laborum.';
		$this->assertEquals($val8, Convert::html2raw($val8), 'Test long text is unwrapped');
		$this->assertEquals(<<<PHP
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed
do eiusmod tempor incididunt ut labore et dolore magna
aliqua. Ut enim ad minim veniam, quis nostrud exercitation
ullamco laboris nisi ut aliquip ex ea commodo consequat.
Duis aute irure dolor in reprehenderit in voluptate velit
esse cillum dolore eu fugiat nulla pariatur. Excepteur sint
occaecat cupidatat non proident, sunt in culpa qui officia
deserunt mollit anim id est laborum.
PHP
			,
			Convert::html2raw($val8, false, 60),
			'Test long text is wrapped'
		);
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

	/**
	 * Tests {@link Convert::raw2htmlid()}
	 */
	public function testRaw2HtmlID() {
		$val1 = 'test test 123';
		$this->assertEquals('test_test_123', Convert::raw2htmlid($val1));

		$val1 = 'test[test][123]';
		$this->assertEquals('test_test_123', Convert::raw2htmlid($val1));

		$val1 = '[test[[test]][123]]';
		$this->assertEquals('test_test_123', Convert::raw2htmlid($val1));
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

	/**
	 * Tests {@link Convert::xml2raw()}
	 */
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

	/**
	 * Tests {@link Convert::json2array()}
	 */
	public function testJSON2Array() {
		$val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
		$decoded = Convert::json2array($val);
		$this->assertEquals(3, count($decoded), '3 items in the decoded array');
		$this->assertContains('Bloggs', $decoded, 'Contains "Bloggs" value in decoded array');
		$this->assertContains('Jones', $decoded, 'Contains "Jones" value in decoded array');
		$this->assertContains('Structure', $decoded['My']['Complicated']);
	}

	/**
	 * Tests {@link Convert::testJSON2Obj()}
	 */
	public function testJSON2Obj() {
		$val = '{"Joe":"Bloggs","Tom":"Jones","My":{"Complicated":"Structure"}}';
		$obj = Convert::json2obj($val);
		$this->assertEquals('Bloggs', $obj->Joe);
		$this->assertEquals('Jones', $obj->Tom);
		$this->assertEquals('Structure', $obj->My->Complicated);
	}

	/**
	 * Tests {@link Convert::testRaw2URL()}
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
	 * @param string $expected
	 * @param string $actual
	 */
	protected function assertEqualsQuoted($expected, $actual) {
		$message = sprintf(
			"Expected \"%s\" but given \"%s\"",
			addcslashes($expected, "\r\n"),
			addcslashes($actual, "\r\n")
		);
		$this->assertEquals($expected, $actual, $message);
	}

	/**
	 * Tests {@link Convert::nl2os()}
	 */
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

	/**
	 * Tests {@link Convert::raw2js()}
	 */
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

	/**
	 * Tests {@link Convert::raw2json()}
	 */
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

	/**
	 * Tests {@link Convert::xml2array()}
	 */
	public function testXML2Array() {
		// Ensure an XML file at risk of entity expansion can be avoided safely
		$inputXML = <<<XML
<?xml version="1.0"?>
<!DOCTYPE results [<!ENTITY long "SOME_SUPER_LONG_STRING">]>
<results>
    <result>Now include &long; lots of times to expand the in-memory size of this XML structure</result>
    <result>&long;&long;&long;</result>
</results>
XML
			;
		try {
			Convert::xml2array($inputXML, true);
		} catch(Exception $ex) {}
		$this->assertTrue(
			isset($ex)
			&& $ex instanceof InvalidArgumentException
			&& $ex->getMessage() === 'XML Doctype parsing disabled'
		);

		// Test without doctype validation
		$expected = array(
			'result' => array(
				"Now include SOME_SUPER_LONG_STRING lots of times to expand the in-memory size of this XML structure",
				array(
					'long' => array(
						array(
							'long' => 'SOME_SUPER_LONG_STRING'
						),
						array(
							'long' => 'SOME_SUPER_LONG_STRING'
						),
						array(
							'long' => 'SOME_SUPER_LONG_STRING'
						)
					)
				)
			)
		);
		$result = Convert::xml2array($inputXML, false, true);
		$this->assertEquals(
			$expected,
			$result
		);
		$result = Convert::xml2array($inputXML, false, false);
		$this->assertEquals(
			$expected,
			$result
		);
	}

	/**
	 * Tests {@link Convert::base64url_encode()} and {@link Convert::base64url_decode()}
	 */
	public function testBase64url() {
		$data = 'Wëīrð characters ☺ such as ¤Ø¶÷╬';
		// This requires this test file to have UTF-8 character encoding
		$this->assertEquals(
			$data, 
			Convert::base64url_decode(Convert::base64url_encode($data))
		);
		
		$data = 654.423;
		$this->assertEquals(
			$data,
			Convert::base64url_decode(Convert::base64url_encode($data))
		);
		
		$data = true;
		$this->assertEquals(
			$data,
			Convert::base64url_decode(Convert::base64url_encode($data))
		);
		
		$data = array('simple','array','¤Ø¶÷╬');
		$this->assertEquals(
			$data,
			Convert::base64url_decode(Convert::base64url_encode($data))
		);
		
		$data = array(
			'a'  => 'associative',
			4    => 'array',
			'☺' => '¤Ø¶÷╬'
		);
		$this->assertEquals(
			$data,
			Convert::base64url_decode(Convert::base64url_encode($data))
		);
	}
}
