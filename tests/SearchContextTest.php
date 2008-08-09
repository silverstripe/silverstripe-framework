<?php

class SearchContextTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/SearchContextTest.yml';
	
	function testResultSetFilterReturnsExpectedCount() {
		$person = singleton('PersonBubble');
		$context = $person->getDefaultSearchContext();
		
		$results = $context->getResultSet(array('Name'=>''));
		$this->assertEquals(5, $results->Count());
		
		$results = $context->getResultSet(array('EyeColor'=>'green'));
		$this->assertEquals(2, $results->Count());
		
		$results = $context->getResultSet(array('EyeColor'=>'green', 'HairColor'=>'black'));
		$this->assertEquals(1, $results->Count());
	}
	
	//function 
	
}

class PersonBubble extends DataObject {
	
	static $db = array(
		"Name" => "Text",
		"Email" => "Text",
		"HairColor" => "Text",
		"EyeColor" => "Text"
	);
	
}

?>