<?php
/**
 * @package sapphire
 * @subpackage tests
 */

class DataDifferencerTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/model/DataDifferencerTest.yml';
	
	protected $extraDataObjects = array(
		'DataDifferencerTest_Object',
		'DataDifferencerTest_HasOneRelationObject',
		'DataDifferencerTest_MockImage',
	);
	
	function testArrayValues() {
		$obj1 = $this->objFromFixture('DataDifferencerTest_Object', 'obj1');
		// create a new version
		$obj1->Choices = array('a');
		$obj1->write();
		$obj1v1 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version-1);
		$obj1v2 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version);
		$differ = new DataDifferencer($obj1v1, $obj1v2);
		$obj1Diff = $differ->diffedData();
		// TODO Using getter would split up field again, bug only caused by simulating
		// an array-based value in the first place.
		$this->assertContains('<ins>a</ins>  <del>a,b</del>', $obj1Diff->getField('Choices'));
	}
	
	function testHasOnes() {
		$obj1 = $this->objFromFixture('DataDifferencerTest_Object', 'obj1');
		$image1 = $this->objFromFixture('DataDifferencerTest_MockImage', 'image1');
		$image2 = $this->objFromFixture('DataDifferencerTest_MockImage', 'image2');
		$relobj1 = $this->objFromFixture('DataDifferencerTest_HasOneRelationObject', 'relobj1');
		$relobj2 = $this->objFromFixture('DataDifferencerTest_HasOneRelationObject', 'relobj2');

		// create a new version
		$obj1->ImageID = $image2->ID;
		$obj1->HasOneRelationID = $relobj2->ID;
		$obj1->write();
		$obj1v1 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version-1);
		$obj1v2 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version);
		$differ = new DataDifferencer($obj1v1, $obj1v2);
		$obj1Diff = $differ->diffedData();
		$this->assertContains($image1->Filename, $obj1Diff->getField('Image'));
		$this->assertContains($image2->Filename, $obj1Diff->getField('Image'));
		$this->assertContains('<ins>obj2</ins>  <del>obj1</del>', $obj1Diff->getField('HasOneRelationID'));
	}
	
	function testNonHtmlFieldsAreEscaped() {
		$obj = new DataDifferencerTest_Object(array(
			'HtmlProperty' => 'HtmlProperty<b>before</b>',
			'TextProperty' => 'TextProperty<b>before</b>',
		));
		$obj->write();
		$obj->update(array(
			'HtmlProperty' => 'HtmlProperty<i>after</i>',
			'TextProperty' => 'TextProperty<i>after</i>',
		));
		$obj->write();
		
		$objv1 = Versioned::get_version('DataDifferencerTest_Object', $obj->ID, $obj->Version-1);
		$objv2 = Versioned::get_version('DataDifferencerTest_Object', $obj->ID, $obj->Version);
		
		// On comparing new record to nothing
		$differ = new DataDifferencer(null, $objv1);
		$objDiff = $differ->diffedData();
		$this->assertEquals(
			'<ins>TextProperty&lt;b&gt;before&lt;/b&gt;</ins>', 
			str_replace(' ', '', $objDiff->TextProperty),
			'New record comparisons are escaped for non-xml properties'
		);
		$this->assertEquals(
			'<ins>HtmlProperty<b>before</b></ins>', 
			str_replace(' ', '', $objDiff->HtmlProperty),
			'New record comparisons are not escaped for xml properties'
		);
		
		// On comparing two records
		$differ = new DataDifferencer($objv1, $objv2);
		$objDiff = $differ->diffedData();
		$this->assertEquals(
			'TextProperty<ins>&lt;i&gt;after&lt;/i&gt;</ins><del>&lt;b&gt;before&lt;/b&gt;</del>', 
			str_replace(' ', '', $objDiff->TextProperty),
			'Record comparisons are escaped for non-xml properties'
		);
		$this->assertEquals(
			'HtmlProperty<ins><i>after</i></ins><del><b>before</b></del>', 
			str_replace(' ', '', $objDiff->HtmlProperty),
			'Record comparisons are not escaped for xml properties'
		);
	}
}

class DataDifferencerTest_Object extends DataObject implements TestOnly {

	static $extensions = array('Versioned("Stage", "Live")');

	static $db = array(
		'Choices' => "Varchar",
		'HtmlProperty' => 'HTMLText',
		'TextProperty' => 'Text',
	);
	
	static $has_one = array(
		'Image' => 'DataDifferencerTest_MockImage',
		'HasOneRelation' => 'DataDifferencerTest_HasOneRelationObject'
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

class DataDifferencerTest_HasOneRelationObject extends DataObject implements TestOnly {
	
	static $db = array(
		'Title' => 'Varchar'
	);
	
	static $has_many = array(
		'Objects' => 'DataDifferencerTest_Object'
	);
}

class DataDifferencerTest_MockImage extends Image implements TestOnly {
	function generateFormattedImage($format, $arg1 = null, $arg2 = null) {
		$cacheFile = $this->cacheFilename($format, $arg1, $arg2);
		$gd = new GD(Director::baseFolder()."/" . $this->Filename);
		// Skip aktual generation
		return $gd;
	}
}