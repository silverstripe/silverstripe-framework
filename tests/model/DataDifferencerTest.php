<?php
/**
 * @package sapphire
 * @subpackage tests
 */

class DataDifferencerTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/model/DataDifferencerTest.yml';
	
	protected $extraDataObjects = array('DataDifferencerTest_Object');
	
	function testArrayValues() {
		$obj1 = $this->objFromFixture('DataDifferencerTest_Object', 'obj1');
		// create a new version
		$obj1->Choices = array('a');
		$obj1->write();
		$obj1v1 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, 1);
		$obj1v2 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, 2);
		$differ = new DataDifferencer($obj1v1, $obj1v2);
		$obj1Diff = $differ->diffedData();
		// TODO Using getter would split up field again, bug only caused by simulating
		// an array-based value in the first place.
		$this->assertContains('<ins>a</ins>  <del>a,b</del>', $obj1Diff->getField('Choices'));
	}
}

class DataDifferencerTest_Object extends DataObject implements TestOnly {

	static $extensions = array('Versioned("Stage", "Live")');

	static $db = array(
		'Choices' => "Varchar",
	);
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$choices = array(
			'a' => 'a',
			'b' => 'b',
			'c' => 'c',
		);
		$listField = new ListboxField('Choices', 'Choices', $choices);
		$fields->push($listField);
		
		return $fields;
	}
	
	function getChoices() {
		return explode(',', $this->getField('Choices'));
	}
	
	function setChoices($val) { 
		$this->setField('Choices', (is_array($val)) ? implode(',', $val) : $val);
	}
	
}