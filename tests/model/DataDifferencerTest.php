<?php

use Filesystem as SS_Filesystem;

/**
 * @package framework
 * @subpackage tests
 */

class DataDifferencerTest extends SapphireTest {

	protected static $fixture_file = 'DataDifferencerTest.yml';

	protected $extraDataObjects = array(
		'DataDifferencerTest_Object',
		'DataDifferencerTest_HasOneRelationObject'
	);

	public function setUp() {
		parent::setUp();

		// Set backend root to /DataDifferencerTest
		AssetStoreTest_SpyStore::activate('DataDifferencerTest');

		// Create a test files for each of the fixture references
		$files = File::get()->exclude('ClassName', 'Folder');
		foreach($files as $file) {
			$fromPath = BASE_PATH . '/framework/tests/model/testimages/' . $file->Name;
			$destPath = BASE_PATH . $file->getURL(); // Only correct for test asset store
			SS_Filesystem::makeFolder(dirname($destPath));
			copy($fromPath, $destPath);
		}
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}

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
		$image1 = $this->objFromFixture('Image', 'image1');
		$image2 = $this->objFromFixture('Image', 'image2');
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
		
		$this->assertContains($image1->Name, $obj1Diff->getField('Image'));
		$this->assertContains($image2->Name, $obj1Diff->getField('Image'));
		$this->assertContains(
			'<ins>obj2</ins><del>obj1</del>',
			str_replace(' ', '', $obj1Diff->getField('HasOneRelationID'))
		);
	}
}

class DataDifferencerTest_Object extends DataObject implements TestOnly {

	private static $extensions = array('Versioned("Stage", "Live")');

	private static $db = array(
		'Choices' => "Varchar",
	);

	private static $has_one = array(
		'Image' => 'Image',
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