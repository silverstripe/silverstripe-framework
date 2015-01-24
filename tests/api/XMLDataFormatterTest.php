<?php
class XMLDataFormatterTest extends SapphireTest {
	protected $arguments, $contents, $tagName;

	protected static $fixture_file = 'XMLDataFormatterTest.yml';

	public function setUp() {
		ShortcodeParser::get_active()->register('test_shortcode', array($this, 'shortcodeSaver'));

		parent::setUp();
	}

	public function tearDown() {
		ShortcodeParser::get_active()->unregister('test_shortcode');

		parent::tearDown();
	}

	protected $extraDataObjects = array(
		'XMLDataFormatterTest_DataObject'
	);

	public function testConvertDataObjectWithoutHeader() {
		$formatter = new XMLDataFormatter();
		$obj = $this->objFromFixture('XMLDataFormatterTest_DataObject', 'test-do');
		$xml = new SimpleXMLElement('<?xml version="1.0"?>' . $formatter->convertDataObjectWithoutHeader($obj));
		$this->assertEquals(
			Director::absoluteBaseURL() . sprintf('api/v1/XMLDataFormatterTest_DataObject/%d.xml', $obj->ID),
			(string) $xml['href']
		);
		$this->assertEquals('Test DataObject', (string) $xml->Name);
		$this->assertEquals('Test Company', (string) $xml->Company);
		$this->assertEquals($obj->ID, (int) $xml->ID);
		$this->assertEquals(
			'<Content><![CDATA[<a href="http://mysite.com">mysite.com</a> is a link in this HTML content.]]>'
				. '</Content>',
			$xml->Content->asXML()
		);
		$this->assertEquals(
			'<a href="http://mysite.com">mysite.com</a> is a link in this HTML content.',
			(string)$xml->Content
		);
	}

	public function testShortcodesInDataObject() {
		$formatter = new XMLDataFormatter();

		$page = new XMLDataFormatterTest_DataObject();
		$page->Content = 'This is some test content [test_shortcode]test[/test_shortcode]';

		$xml = new SimpleXMLElement('<?xml version="1.0"?>' . $formatter->convertDataObjectWithoutHeader($page));
		$this->assertEquals('This is some test content test', (string)$xml->Content);

		$page->Content = '[test_shortcode,id=-1]';
		$xml = new SimpleXMLElement('<?xml version="1.0"?>' . $formatter->convertDataObjectWithoutHeader($page));
		$this->assertEmpty('', (string)$xml->Content);

		$page->Content = '[bad_code,id=1]';

		$xml = new SimpleXMLElement('<?xml version="1.0"?>' . $formatter->convertDataObjectWithoutHeader($page));
		$this->assertContains('[bad_code,id=1]', (string)$xml->Content);
	}

	/**
	 * Stores the result of a shortcode parse in object properties for easy testing access.
	 */
	public function shortcodeSaver($arguments, $content = null, $parser, $tagName = null) {
		$this->arguments = $arguments;
		$this->contents  = $content;
		$this->tagName   = $tagName;

		return $content;
	}

}
class XMLDataFormatterTest_DataObject extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar(50)',
		'Company' => 'Varchar(50)',
		'Content' => 'HTMLText'
	);

}
