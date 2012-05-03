<?php
class XMLDataFormatterTest extends SapphireTest {

	public static $fixture_file = 'XMLDataFormatterTest.yml';

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
			'<Content><![CDATA[<a href="http://mysite.com">mysite.com</a> is a link in this HTML content. <![CDATA[this is some nested CDATA]]]]><![CDATA[>]]></Content>',
			$xml->Content->asXML()
		);
		$this->assertEquals(
			'<a href="http://mysite.com">mysite.com</a> is a link in this HTML content. <![CDATA[this is some nested CDATA]]>',
			(string) $xml->Content
		);
	}

}
class XMLDataFormatterTest_DataObject extends DataObject implements TestOnly {

	public static $db = array(
		'Name' => 'Varchar(50)',
		'Company' => 'Varchar(50)',
		'Content' => 'HTMLText'
	);

}
