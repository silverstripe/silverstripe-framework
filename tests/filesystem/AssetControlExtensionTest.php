<?php
use SilverStripe\Filesystem\Storage\AssetStore;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;



/**
 * Tests {@see AssetControlExtension}
 */
class AssetControlExtensionTest extends SapphireTest {

	protected $extraDataObjects = array(
		'AssetControlExtensionTest_VersionedObject',
		'AssetControlExtensionTest_Object'
	);

	public function setUp() {
		parent::setUp();

		// Set backend and base url
		Versioned::set_stage(Versioned::DRAFT);
		AssetStoreTest_SpyStore::activate('AssetControlExtensionTest');
		$this->logInWithPermission('ADMIN');

		// Setup fixture manually
		$object1 = new AssetControlExtensionTest_VersionedObject();
		$object1->Title = 'My object';
		$fish1 = realpath(__DIR__ .'/../model/testimages/test-image-high-quality.jpg');
		$object1->Header->setFromLocalFile($fish1, 'Header/MyObjectHeader.jpg');
		$object1->Download->setFromString('file content', 'Documents/File.txt');
		$object1->write();
		$object1->publishSingle();

		$object2 = new AssetControlExtensionTest_Object();
		$object2->Title = 'Unversioned';
		$object2->Image->setFromLocalFile($fish1, 'Images/BeautifulFish.jpg');
		$object2->write();

		$object3 = new AssetControlExtensionTest_ArchivedObject();
		$object3->Title = 'Archived';
		$object3->Header->setFromLocalFile($fish1, 'Archived/MyObjectHeader.jpg');
		$object3->write();
		$object3->publishSingle();
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}

	public function testFileDelete() {
		Versioned::set_stage(Versioned::DRAFT);

		/** @var AssetControlExtensionTest_VersionedObject $object1 */
		$object1 = AssetControlExtensionTest_VersionedObject::get()
				->filter('Title', 'My object')
				->first();
		/** @var AssetControlExtensionTest_Object $object2 */
		$object2 = AssetControlExtensionTest_Object::get()
				->filter('Title', 'Unversioned')
				->first();

		/** @var AssetControlExtensionTest_ArchivedObject $object3 */
		$object3 = AssetControlExtensionTest_ArchivedObject::get()
				->filter('Title', 'Archived')
				->first();

		$this->assertTrue($object1->Download->exists());
		$this->assertTrue($object1->Header->exists());
		$this->assertTrue($object2->Image->exists());
		$this->assertTrue($object3->Header->exists());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object1->Download->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object1->Header->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object2->Image->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object3->Header->getVisibility());

		// Check live stage for versioned objects
		$object1Live = Versioned::get_one_by_stage('AssetControlExtensionTest_VersionedObject', 'Live',
			array('"ID"' => $object1->ID)
		);
		$object3Live = Versioned::get_one_by_stage('AssetControlExtensionTest_ArchivedObject', 'Live',
			array('"ID"' => $object3->ID)
		);
		$this->assertTrue($object1Live->Download->exists());
		$this->assertTrue($object1Live->Header->exists());
		$this->assertTrue($object3Live->Header->exists());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object1Live->Download->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object1Live->Header->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object3Live->Header->getVisibility());

		// Delete live records; Should cause versioned records to be protected
		$object1Live->deleteFromStage('Live');
		$object3Live->deleteFromStage('Live');
		$this->assertTrue($object1->Download->exists());
		$this->assertTrue($object1->Header->exists());
		$this->assertTrue($object3->Header->exists());
		$this->assertTrue($object1Live->Download->exists());
		$this->assertTrue($object1Live->Header->exists());
		$this->assertTrue($object3Live->Header->exists());
		$this->assertEquals(AssetStore::VISIBILITY_PROTECTED, $object1->Download->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PROTECTED, $object1->Header->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PROTECTED, $object3->Header->getVisibility());

		// Delete draft record; Should remove all records
		// Archived assets only should remain
		$object1->delete();
		$object2->delete();
		$object3->delete();
		$this->assertFalse($object1->Download->exists());
		$this->assertFalse($object1->Header->exists());
		$this->assertFalse($object2->Image->exists());
		$this->assertTrue($object3->Header->exists());
		$this->assertFalse($object1Live->Download->exists());
		$this->assertFalse($object1Live->Header->exists());
		$this->assertTrue($object3Live->Header->exists());
		$this->assertNull($object1->Download->getVisibility());
		$this->assertNull($object1->Header->getVisibility());
		$this->assertNull($object2->Image->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PROTECTED, $object3->Header->getVisibility());
	}

	/**
	 * Test files being replaced
	 */
	public function testReplaceFile() {
		Versioned::set_stage(Versioned::DRAFT);

		/** @var AssetControlExtensionTest_VersionedObject $object1 */
		$object1 = AssetControlExtensionTest_VersionedObject::get()
				->filter('Title', 'My object')
				->first();
		/** @var AssetControlExtensionTest_Object $object2 */
		$object2 = AssetControlExtensionTest_Object::get()
				->filter('Title', 'Unversioned')
				->first();

		/** @var AssetControlExtensionTest_ArchivedObject $object3 */
		$object3 = AssetControlExtensionTest_ArchivedObject::get()
				->filter('Title', 'Archived')
				->first();

		$object1TupleOld = $object1->Header->getValue();
		$object2TupleOld = $object2->Image->getValue();
		$object3TupleOld = $object3->Header->getValue();

		// Replace image and write each to filesystem
		$fish1 = realpath(__DIR__ .'/../model/testimages/test-image-high-quality.jpg');
		$object1->Header->setFromLocalFile($fish1, 'Header/Replaced_MyObjectHeader.jpg');
		$object1->write();
		$object2->Image->setFromLocalFile($fish1, 'Images/Replaced_BeautifulFish.jpg');
		$object2->write();
		$object3->Header->setFromLocalFile($fish1, 'Archived/Replaced_MyObjectHeader.jpg');
		$object3->write();

		// Check that old published records are left public, but removed for unversioned object2
		$this->assertEquals(
			AssetStore::VISIBILITY_PUBLIC,
			$this->getAssetStore()->getVisibility($object1TupleOld['Filename'], $object1TupleOld['Hash'])
		);
		$this->assertEquals(
			null, // Old file is destroyed
			$this->getAssetStore()->getVisibility($object2TupleOld['Filename'], $object2TupleOld['Hash'])
		);
		$this->assertEquals(
			AssetStore::VISIBILITY_PUBLIC,
			$this->getAssetStore()->getVisibility($object3TupleOld['Filename'], $object3TupleOld['Hash'])
		);

		// Check that visibility of new file is correct
		// Note that $object2 has no canView() is true, so assets end up public
		$this->assertEquals(AssetStore::VISIBILITY_PROTECTED, $object1->Header->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object2->Image->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PROTECTED, $object3->Header->getVisibility());

		// Publish changes to versioned records
		$object1->publishSingle();
		$object3->publishSingle();

		// After publishing, old object1 is deleted, but since object3 has archiving enabled,
		// the orphaned file is intentionally left in the protected store
		$this->assertEquals(
			null,
			$this->getAssetStore()->getVisibility($object1TupleOld['Filename'], $object1TupleOld['Hash'])
		);
		$this->assertEquals(
			AssetStore::VISIBILITY_PROTECTED,
			$this->getAssetStore()->getVisibility($object3TupleOld['Filename'], $object3TupleOld['Hash'])
		);

		// And after publish, all files are public
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object1->Header->getVisibility());
		$this->assertEquals(AssetStore::VISIBILITY_PUBLIC, $object3->Header->getVisibility());
	}

	/**
	 * @return AssetStore
	 */
	protected function getAssetStore() {
		return Injector::inst()->get('AssetStore');
	}

}

/**
 * Versioned object with attached assets
 *
 * @property string $Title
 * @property DBFile $Header
 * @property DBFile $Download
 * @mixin Versioned
 */
class AssetControlExtensionTest_VersionedObject extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned'
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Header' => "DBFile('image/supported')",
		'Download' => 'DBFile'
	);

	/**
	 * @param Member $member
	 * @return bool
	 */
	public function canView($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}

		// Expectation that versioned::canView will hide this object in draft
		$result = $this->extendedCan('canView', $member);
		if($result !== null) {
			return $result;
		}

		// Open to public
		return true;
	}
}

/**
 * A basic unversioned object
 *
 * @property string $Title
 * @property DBFile $Image
 */
class AssetControlExtensionTest_Object extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar(255)',
		'Image' => "DBFile('image/supported')"
	);


	/**
	 * @param Member $member
	 * @return bool
	 */
	public function canView($member = null) {
		return true;
	}
}

/**
 * Versioned object that always archives its assets
 */
class AssetControlExtensionTest_ArchivedObject extends AssetControlExtensionTest_VersionedObject {
	private static $keep_archived_assets = true;
}
