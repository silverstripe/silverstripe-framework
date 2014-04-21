<?php
class JSONDataFormatterTest extends SapphireTest {
	
	public function testConvertDataObjectWithSpecialCharacters() {
		$formatter = new JSONDataFormatter();
		$formatter->setCustomAddFields(array('Content'));
		
		$obj = new JSONDataFormatterTest_DataObject();
		// Content: <p>a 'test' "case" </p>
		$obj->Content = "<p>a 'test' \"case\" </p>";

		$encoded = $formatter->convertDataObject($obj, array('Content'));

		// converted JSON: {"Content":"<p>a 'test' \"case\" <\/p>"}
		$this->assertEquals('{"Content":"<p>a \'test\' \\"case\\" <\/p>"}', $encoded);
	}

}

class JSONDataFormatterTest_DataObject extends DataObject implements TestOnly {

	private static $db = array(
		'Content' => 'Varchar(50)'
	);

}
