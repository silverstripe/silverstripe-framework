<?php

use Filesystem as SS_Filesystem;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\Versioning\DataDifferencer;
use SilverStripe\ORM\DataObject;


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

		Versioned::set_stage(Versioned::DRAFT);

		// Set backend root to /DataDifferencerTest
		AssetStoreTest_SpyStore::activate('DataDifferencerTest');

		// Create a test files for each of the fixture references
		$files = File::get()->exclude('ClassName', 'Folder');
		foreach($files as $file) {
			$fromPath = BASE_PATH . '/framework/tests/model/testimages/' . $file->Name;
			$destPath = AssetStoreTest_SpyStore::getLocalPath($file); // Only correct for test asset store
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
		$beforeVersion = $obj1->Version;
		// create a new version
		$obj1->Choices = 'a';
		$obj1->write();
		$afterVersion = $obj1->Version;
		$obj1v1 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $beforeVersion);
		$obj1v2 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $afterVersion);
		$differ = new DataDifferencer($obj1v1, $obj1v2);
		$obj1Diff = $differ->diffedData();
		// TODO Using getter would split up field again, bug only caused by simulating
		// an array-based value in the first place.
		$this->assertContains('<ins>a</ins><del>a,b</del>', str_replace(' ','',$obj1Diff->getField('Choices')));
	}

	public function testHasOnes() {
		/** @var DataDifferencerTest_Object $obj1 */
		$obj1 = $this->objFromFixture('DataDifferencerTest_Object', 'obj1');
		$image1 = $this->objFromFixture('Image', 'image1');
		$image2 = $this->objFromFixture('Image', 'image2');
		$relobj1 = $this->objFromFixture('DataDifferencerTest_HasOneRelationObject', 'relobj1');
		$relobj2 = $this->objFromFixture('DataDifferencerTest_HasOneRelationObject', 'relobj2');

		// create a new version
		$beforeVersion = $obj1->Version;
		$obj1->ImageID = $image2->ID;
		$obj1->HasOneRelationID = $relobj2->ID;
		$obj1->write();
		$afterVersion = $obj1->Version;
		$this->assertNotEquals($beforeVersion, $afterVersion);
		/** @var DataDifferencerTest_Object $obj1v1 */
		$obj1v1 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $beforeVersion);
		/** @var DataDifferencerTest_Object $obj1v2 */
		$obj1v2 = Versioned::get_version('DataDifferencerTest_Object', $obj1->ID, $afterVersion);
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

/**
 * @property string $Choices
 * @method Image Image()
 * @method DataDifferencerTest_HasOneRelationObject HasOneRelation()
 */
class DataDifferencerTest_Object extends DataObject implements TestOnly {

	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned'
	);

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
