<?php
/**
 * Test the {@link DataObjectSet} class.
 * 
 * @package sapphire
 * @subpackage tests
 */
class DataObjectSetTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/DataObjectTest.yml';
	
	/**
	 * Test {@link DataObjectSet->Count()}
	 */
	function testCount() {
		$comments = DataObject::get('PageComment');
		
		/* There are a total of 8 items in the set */
		$this->assertEquals($comments->Count(), 8, 'There are a total of 8 items in the set');
	}

	/**
	 * Test {@link DataObjectSet->First()}
	 */
	function testFirst() {
		$comments = DataObject::get('PageComment');
		
		/* The first object is Joe's comment */
		$this->assertEquals($comments->First()->ID, 1, 'The first object has an ID of "1"');
		$this->assertEquals($comments->First()->Name, 'Joe', 'The first object has a Name field value of "Joe"');
	}
	
	/**
	 * Test {@link DataObjectSet->Last()}
	 */
	function testLast() {
		$comments = DataObject::get('PageComment');
		
		/* The last object is Dean's comment */
		$this->assertEquals($comments->Last()->ID, 8, 'The last object has an ID of "8"');
		$this->assertEquals($comments->Last()->Name, 'Dean', 'The last object has a Name field value of "Dean"');
	}
	
	/**
	 * Test {@link DataObjectSet->map()}
	 */
	function testMap() {
		$comments = DataObject::get('PageComment');

		/* Now we get a map of all the PageComment records */
		$map = $comments->map('ID', 'Title', '(Select one)');
		
		$expectedMap = array(
			'' => '(Select one)',
			1 => 'Joe',
			2 => 'Jane',
			3 => 'Bob',
			4 => 'Bob',
			5 => 'Ernie',
			6 => 'Jimmy',
			7 => 'Dean',
			8 => 'Dean'
		);
		
		/* There are 9 items in the map. 8 are records. 1 is the empty value */
		$this->assertEquals(count($map), 9, 'There are 9 items in the map. 8 are records. 1 is the empty value');
		
		/* We have the same map as our expected map, asserted above */
		$this->assertSame($expectedMap, $map, 'The map we generated is exactly the same as the asserted one');
		
		/* toDropDownMap() is an alias of map() - let's make a map from that */
		$map2 = $comments->toDropDownMap('ID', 'Title', '(Select one)');
		
		/* There are 9 items in the map. 8 are records. 1 is the empty value */
		$this->assertEquals(count($map), 9, 'There are 9 items in the map. 8 are records. 1 is the empty value.');
		
		/* We have the same map as our expected map, asserted above */
		$this->assertSame($expectedMap, $map2, 'The map we generated is exactly the same as the asserted one');
	}
	
}
?>