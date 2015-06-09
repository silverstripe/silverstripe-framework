<?php
/**
 * @package framework
 * @subpackage tests
 */

class DataDifferencerTest extends SapphireTest {

	protected static $fixture_file = 'DataDifferencerTest.yml';

	protected $extraDataObjects = array(
		'DataDifferencerTest_Object',
		'DataDifferencerTest_HasOneRelationObject',
		'DataDifferencerTest_MockImage',
	);

	public function testArrayValues() {
		$obj1 = $this->objFromFixture('DataDifferencerTest_Object', 'obj1');
		// create a new version
		$obj1->Choices = 'a';
		$obj1->write();
		$obj1v1 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version-1);
		$obj1v2 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version);
		$differ = new DataDifferencer($obj1v1, $obj1v2);
		$obj1Diff = $differ->diffedData();
		// TODO Using getter would split up field again, bug only caused by simulating
		// an array-based value in the first place.
		$this->assertContains('<ins>a</ins><del>a,b</del>', str_replace(' ','',$obj1Diff->getField('Choices')));
	}

	public function testHasOnes() {
		$obj1 = $this->objFromFixture('DataDifferencerTest_Object', 'obj1');
		$image1 = $this->objFromFixture('DataDifferencerTest_MockImage', 'image1');
		$image2 = $this->objFromFixture('DataDifferencerTest_MockImage', 'image2');
		$relobj1 = $this->objFromFixture('DataDifferencerTest_HasOneRelationObject', 'relobj1');
		$relobj2 = $this->objFromFixture('DataDifferencerTest_HasOneRelationObject', 'relobj2');

		// in order to ensure the Filename path is correct, append the correct FRAMEWORK_DIR to the start
		// this is only really necessary to make the test pass when FRAMEWORK_DIR is not "framework"
		$image1->Filename = FRAMEWORK_DIR . substr($image1->Filename, 9);
		$image2->Filename = FRAMEWORK_DIR . substr($image2->Filename, 9);
		$origUpdateFilesystem = Config::inst()->get('File', 'update_filesystem');
		// we don't want the filesystem being updated on write, as we're only dealing with mock files
		Config::inst()->update('File', 'update_filesystem', false);
		$image1->write();
		$image2->write();
		Config::inst()->update('File', 'update_filesystem', $origUpdateFilesystem);

		// create a new version
		$obj1->ImageID = $image2->ID;
		$obj1->HasOneRelationID = $relobj2->ID;
		$obj1->write();
		$obj1v1 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version-1);
		$obj1v2 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $obj1->Version);
		$differ = new DataDifferencer($obj1v1, $obj1v2);
		$obj1Diff = $differ->diffedData();

		$this->assertContains($image1->Name, $obj1Diff->getField('Image'));
		$this->assertContains($image2->Name, $obj1Diff->getField('Image'));
		$this->assertContains('<ins>obj2</ins><del>obj1</del>',
			str_replace(' ','',$obj1Diff->getField('HasOneRelationID')));
	}
}

class DataDifferencerTest_Object extends DataObject implements TestOnly {

	private static $extensions = array('Versioned("Stage", "Live")');

	private static $db = array(
		'Choices' => "Varchar",
	);

	private static $has_one = array(
		'Image' => 'DataDifferencerTest_MockImage',
		'HasOneRelation' => 'DataDifferencerTest_HasOneRelationObject'
	);

	public function getCMSFields() {
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
	
}

class DataDifferencerTest_HasOneRelationObject extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar'
	);

	private static $has_many = array(
		'Objects' => 'DataDifferencerTest_Object'
	);
}

class DataDifferencerTest_MockImage extends Image implements TestOnly {
	public function generateFormattedImage($format, $arg1 = null, $arg2 = null) {
		$cacheFile = $this->cacheFilename($format, $arg1, $arg2);
		$gd = new GDBackend(Director::baseFolder()."/" . $this->Filename);
		// Skip aktual generation
		return $gd;
	}
}
