<?php

use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Tests ownership API of versioned DataObjects
 */
class VersionedOwnershipTest extends SapphireTest {

	protected $extraDataObjects = array(
		'VersionedOwnershipTest_Object',
		'VersionedOwnershipTest_Subclass',
		'VersionedOwnershipTest_Related',
		'VersionedOwnershipTest_Attachment',
		'VersionedOwnershipTest_RelatedMany',
		'VersionedOwnershipTest_Page',
		'VersionedOwnershipTest_Banner',
		'VersionedOwnershipTest_Image',
		'VersionedOwnershipTest_CustomRelation',
	);

	protected static $fixture_file = 'VersionedOwnershipTest.yml';

	public function setUp() {
		parent::setUp();

		Versioned::set_stage(Versioned::DRAFT);

		// Automatically publish any object named *_published
		foreach($this->getFixtureFactory()->getFixtures() as $class => $fixtures) {
			foreach($fixtures as $name => $id) {
				if(stripos($name, '_published') !== false) {
					/** @var Versioned|DataObject $object */
					$object = DataObject::get($class)->byID($id);
					$object->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
				}
			}
		}
	}

	/**
	 * Virtual "sleep" that doesn't actually slow execution, only advances DBDateTime::now()
	 *
	 * @param int $minutes
	 */
	protected function sleep($minutes) {
		$now = DBDatetime::now();
		$date = DateTime::createFromFormat('Y-m-d H:i:s', $now->getValue());
		$date->modify("+{$minutes} minutes");
		DBDatetime::set_mock_now($date->format('Y-m-d H:i:s'));
	}

	/**
	 * Test basic findOwned() in stage mode
	 */
	public function testFindOwned() {
		/** @var VersionedOwnershipTest_Subclass $subclass1 */
		$subclass1 = $this->objFromFixture('VersionedOwnershipTest_Subclass', 'subclass1_published');
		$this->assertDOSEquals(
			[
				['Title' => 'Related 1'],
				['Title' => 'Attachment 1'],
				['Title' => 'Attachment 2'],
				['Title' => 'Attachment 5'],
				['Title' => 'Related Many 1'],
				['Title' => 'Related Many 2'],
				['Title' => 'Related Many 3'],
			],
			$subclass1->findOwned()
		);

		// Non-recursive search
		$this->assertDOSEquals(
			[
				['Title' => 'Related 1'],
				['Title' => 'Related Many 1'],
				['Title' => 'Related Many 2'],
				['Title' => 'Related Many 3'],
			],
			$subclass1->findOwned(false)
		);

		/** @var VersionedOwnershipTest_Subclass $subclass2 */
		$subclass2 = $this->objFromFixture('VersionedOwnershipTest_Subclass', 'subclass2_published');
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2'],
				['Title' => 'Attachment 3'],
				['Title' => 'Attachment 4'],
				['Title' => 'Attachment 5'],
				['Title' => 'Related Many 4'],
			],
			$subclass2->findOwned()
		);

		// Non-recursive search
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2'],
				['Title' => 'Related Many 4'],
			],
			$subclass2->findOwned(false)
		);

		/** @var VersionedOwnershipTest_Related $related1 */
		$related1 = $this->objFromFixture('VersionedOwnershipTest_Related', 'related1');
		$this->assertDOSEquals(
			[
				['Title' => 'Attachment 1'],
				['Title' => 'Attachment 2'],
				['Title' => 'Attachment 5'],
			],
			$related1->findOwned()
		);

		/** @var VersionedOwnershipTest_Related $related2 */
		$related2 = $this->objFromFixture('VersionedOwnershipTest_Related', 'related2_published');
		$this->assertDOSEquals(
			[
				['Title' => 'Attachment 3'],
				['Title' => 'Attachment 4'],
				['Title' => 'Attachment 5'],
			],
			$related2->findOwned()
		);
	}

	/**
	 * Test findOwners
	 */
	public function testFindOwners() {
		/** @var VersionedOwnershipTest_Attachment $attachment1 */
		$attachment1 = $this->objFromFixture('VersionedOwnershipTest_Attachment', 'attachment1');
		$this->assertDOSEquals(
			[
				['Title' => 'Related 1'],
				['Title' => 'Subclass 1'],
			],
			$attachment1->findOwners()
		);

		// Non-recursive search
		$this->assertDOSEquals(
			[
				['Title' => 'Related 1'],
			],
			$attachment1->findOwners(false)
		);

		/** @var VersionedOwnershipTest_Attachment $attachment5 */
		$attachment5 = $this->objFromFixture('VersionedOwnershipTest_Attachment', 'attachment5_published');
		$this->assertDOSEquals(
			[
				['Title' => 'Related 1'],
				['Title' => 'Related 2'],
				['Title' => 'Subclass 1'],
				['Title' => 'Subclass 2'],
			],
			$attachment5->findOwners()
		);

		// Non-recursive
		$this->assertDOSEquals(
			[
				['Title' => 'Related 1'],
				['Title' => 'Related 2'],
			],
			$attachment5->findOwners(false)
		);

		/** @var VersionedOwnershipTest_Related $related1 */
		$related1 = $this->objFromFixture('VersionedOwnershipTest_Related', 'related1');
		$this->assertDOSEquals(
			[
				['Title' => 'Subclass 1'],
			],
			$related1->findOwners()
		);
	}

	/**
	 * Test findOwners on Live stage
	 */
	public function testFindOwnersLive() {
		// Modify a few records on stage
		$related2 = $this->objFromFixture('VersionedOwnershipTest_Related', 'related2_published');
		$related2->Title .= ' Modified';
		$related2->write();
		$attachment3 = $this->objFromFixture('VersionedOwnershipTest_Attachment', 'attachment3_published');
		$attachment3->Title .= ' Modified';
		$attachment3->write();
		$attachment4 = $this->objFromFixture('VersionedOwnershipTest_Attachment', 'attachment4_published');
		$attachment4->delete();
		$subclass2ID = $this->idFromFixture('VersionedOwnershipTest_Subclass', 'subclass2_published');

		// Check that stage record is ok
		/** @var VersionedOwnershipTest_Subclass $subclass2Stage */
		$subclass2Stage = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', 'Stage')->byID($subclass2ID);
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2 Modified'],
				['Title' => 'Attachment 3 Modified'],
				['Title' => 'Attachment 5'],
				['Title' => 'Related Many 4'],
			],
			$subclass2Stage->findOwned()
		);

		// Non-recursive
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2 Modified'],
				['Title' => 'Related Many 4'],
			],
			$subclass2Stage->findOwned(false)
		);

		// Live records are unchanged
		/** @var VersionedOwnershipTest_Subclass $subclass2Live */
		$subclass2Live = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', 'Live')->byID($subclass2ID);
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2'],
				['Title' => 'Attachment 3'],
				['Title' => 'Attachment 4'],
				['Title' => 'Attachment 5'],
				['Title' => 'Related Many 4'],
			],
			$subclass2Live->findOwned()
		);

		// Test non-recursive
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2'],
				['Title' => 'Related Many 4'],
			],
			$subclass2Live->findOwned(false)
		);
	}

	/**
	 * Test that objects are correctly published recursively
	 */
	public function testRecursivePublish() {
		/** @var VersionedOwnershipTest_Subclass $parent */
		$parent = $this->objFromFixture('VersionedOwnershipTest_Subclass', 'subclass1_published');
		$parentID = $parent->ID;
		$banner1 = $this->objFromFixture('VersionedOwnershipTest_RelatedMany', 'relatedmany1_published');
		$banner2 = $this->objFromFixture('VersionedOwnershipTest_RelatedMany', 'relatedmany2_published');
		$banner2ID = $banner2->ID;

		// Modify, Add, and Delete banners on stage
		$banner1->Title = 'Renamed Banner 1';
		$banner1->write();

		$banner2->delete();

		$banner4 = new VersionedOwnershipTest_RelatedMany();
		$banner4->Title = 'New Banner';
		$parent->Banners()->add($banner4);

		// Check state of objects before publish
		$oldLiveBanners = [
			['Title' => 'Related Many 1'],
			['Title' => 'Related Many 2'], // Will be deleted
			// `Related Many 3` isn't published
		];
		$newBanners = [
			['Title' => 'Renamed Banner 1'], // Renamed
			['Title' => 'Related Many 3'], // Published without changes
			['Title' => 'New Banner'], // Created
		];
		$parentDraft = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::DRAFT)
			->byID($parentID);
		$this->assertDOSEquals($newBanners, $parentDraft->Banners());
		$parentLive = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::LIVE)
			->byID($parentID);
		$this->assertDOSEquals($oldLiveBanners, $parentLive->Banners());

		// On publishing of owner, all children should now be updated
		$parent->publishRecursive();

		// Now check each object has the correct state
		$parentDraft = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::DRAFT)
			->byID($parentID);
		$this->assertDOSEquals($newBanners, $parentDraft->Banners());
		$parentLive = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::LIVE)
			->byID($parentID);
		$this->assertDOSEquals($newBanners, $parentLive->Banners());

		// Check that the deleted banner hasn't actually been deleted from the live stage,
		// but in fact has been unlinked.
		$banner2Live = Versioned::get_by_stage('VersionedOwnershipTest_RelatedMany', Versioned::LIVE)
			->byID($banner2ID);
		$this->assertEmpty($banner2Live->PageID);
	}

	/**
	 * Test that owning objects get unpublished as needed
	 */
	public function testRecursiveUnpublish() {
		// Unsaved objects can't be unpublished
		$unsaved = new VersionedOwnershipTest_Subclass();
		$this->assertFalse($unsaved->doUnpublish());

		// Draft-only objects can't be unpublished
		/** @var VersionedOwnershipTest_RelatedMany $banner3Unpublished */
		$banner3Unpublished = $this->objFromFixture('VersionedOwnershipTest_RelatedMany', 'relatedmany3');
		$this->assertFalse($banner3Unpublished->doUnpublish());

		// First test: mid-level unpublish; We expect that owners should be unpublished, but not
		// owned objects, nor other siblings shared by the same owner.
		$related2 = $this->objFromFixture('VersionedOwnershipTest_Related', 'related2_published');
		/** @var VersionedOwnershipTest_Attachment $attachment3 */
		$attachment3 = $this->objFromFixture('VersionedOwnershipTest_Attachment', 'attachment3_published');
		/** @var VersionedOwnershipTest_RelatedMany $relatedMany4 */
		$relatedMany4 = $this->objFromFixture('VersionedOwnershipTest_RelatedMany', 'relatedmany4_published');
		/** @var VersionedOwnershipTest_Related $related2 */
		$this->assertTrue($related2->doUnpublish());
		$subclass2 = $this->objFromFixture('VersionedOwnershipTest_Subclass', 'subclass2_published');

		/** @var VersionedOwnershipTest_Subclass $subclass2 */
		$this->assertFalse($subclass2->isPublished()); // Owner IS unpublished
		$this->assertTrue($attachment3->isPublished()); // Owned object is NOT unpublished
		$this->assertTrue($relatedMany4->isPublished()); // Owned object by owner is NOT unpublished

		// Second test: multi-level unpublish should recursively cascade down all owning objects
		// Publish related2 again
		$subclass2->publishRecursive();
		$this->assertTrue($subclass2->isPublished());
		$this->assertTrue($related2->isPublished());
		$this->assertTrue($attachment3->isPublished());

		// Unpublish leaf node
		$this->assertTrue($attachment3->doUnpublish());

		// Now all owning objects (only) are unpublished
		$this->assertFalse($attachment3->isPublished()); // Unpublished because we just unpublished it
		$this->assertFalse($related2->isPublished()); // Unpublished because it owns attachment3
		$this->assertFalse($subclass2->isPublished()); // Unpublished ecause it owns related2
		$this->assertTrue($relatedMany4->isPublished()); // Stays live because recursion only affects owners not owned.
	}

	public function testRecursiveArchive() {
		// When archiving an object, any published owners should be unpublished at the same time
		// but NOT achived

		/** @var VersionedOwnershipTest_Attachment $attachment3 */
		$attachment3 = $this->objFromFixture('VersionedOwnershipTest_Attachment', 'attachment3_published');
		$attachment3ID = $attachment3->ID;
		$this->assertTrue($attachment3->doArchive());

		// This object is on neither stage nor live
		$stageAttachment = Versioned::get_by_stage('VersionedOwnershipTest_Attachment', Versioned::DRAFT)
			->byID($attachment3ID);
		$liveAttachment = Versioned::get_by_stage('VersionedOwnershipTest_Attachment', Versioned::LIVE)
			->byID($attachment3ID);
		$this->assertEmpty($stageAttachment);
		$this->assertEmpty($liveAttachment);

		// Owning object is unpublished only
		/** @var VersionedOwnershipTest_Related $stageOwner */
		$stageOwner = $this->objFromFixture('VersionedOwnershipTest_Related', 'related2_published');
		$this->assertTrue($stageOwner->isOnDraft());
		$this->assertFalse($stageOwner->isPublished());

		// Bottom level owning object is also unpublished
		/** @var VersionedOwnershipTest_Subclass $stageTopOwner */
		$stageTopOwner = $this->objFromFixture('VersionedOwnershipTest_Subclass', 'subclass2_published');
		$this->assertTrue($stageTopOwner->isOnDraft());
		$this->assertFalse($stageTopOwner->isPublished());
	}

	public function testRecursiveRevertToLive() {
		/** @var VersionedOwnershipTest_Subclass $parent */
		$parent = $this->objFromFixture('VersionedOwnershipTest_Subclass', 'subclass1_published');
		$parentID = $parent->ID;
		$banner1 = $this->objFromFixture('VersionedOwnershipTest_RelatedMany', 'relatedmany1_published');
		$banner2 = $this->objFromFixture('VersionedOwnershipTest_RelatedMany', 'relatedmany2_published');
		$banner2ID = $banner2->ID;

		// Modify, Add, and Delete banners on stage
		$banner1->Title = 'Renamed Banner 1';
		$banner1->write();

		$banner2->delete();

		$banner4 = new VersionedOwnershipTest_RelatedMany();
		$banner4->Title = 'New Banner';
		$banner4->write();
		$parent->Banners()->add($banner4);

		// Check state of objects before publish
		$liveBanners = [
			['Title' => 'Related Many 1'],
			['Title' => 'Related Many 2'],
		];
		$modifiedBanners = [
			['Title' => 'Renamed Banner 1'], // Renamed
			['Title' => 'Related Many 3'], // Published without changes
			['Title' => 'New Banner'], // Created
		];
		$parentDraft = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::DRAFT)
			->byID($parentID);
		$this->assertDOSEquals($modifiedBanners, $parentDraft->Banners());
		$parentLive = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::LIVE)
			->byID($parentID);
		$this->assertDOSEquals($liveBanners, $parentLive->Banners());

		// When reverting parent, all records should be put back on stage
		$this->assertTrue($parent->doRevertToLive());

		// Now check each object has the correct state
		$parentDraft = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::DRAFT)
			->byID($parentID);
		$this->assertDOSEquals($liveBanners, $parentDraft->Banners());
		$parentLive = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::LIVE)
			->byID($parentID);
		$this->assertDOSEquals($liveBanners, $parentLive->Banners());

		// Check that the newly created banner, even though it still exist, has been
		// unlinked from the reverted draft record
		/** @var VersionedOwnershipTest_RelatedMany $banner4Draft */
		$banner4Draft = Versioned::get_by_stage('VersionedOwnershipTest_RelatedMany', Versioned::DRAFT)
			->byID($banner4->ID);
		$this->assertTrue($banner4Draft->isOnDraft());
		$this->assertFalse($banner4Draft->isPublished());
		$this->assertEmpty($banner4Draft->PageID);
	}

	/**
	 * Test that rolling back to a single version works recursively
	 */
	public function testRecursiveRollback() {
		/** @var VersionedOwnershipTest_Subclass $subclass2 */
		$this->sleep(1);
		$subclass2 = $this->objFromFixture('VersionedOwnershipTest_Subclass', 'subclass2_published');

		// Create a few new versions
		$versions = [];
		for($version = 1; $version <= 3; $version++) {
			// Write owned objects
			$this->sleep(1);
			foreach($subclass2->findOwned(true) as $obj) {
				$obj->Title .= " - v{$version}";
				$obj->write();
			}
			// Write parent
			$this->sleep(1);
			$subclass2->Title .= " - v{$version}";
			$subclass2->write();
			$versions[$version] = $subclass2->Version;
		}


		// Check reverting to first version
		$this->sleep(1);
		$subclass2->doRollbackTo($versions[1]);
		/** @var VersionedOwnershipTest_Subclass $subclass2Draft */
		$subclass2Draft = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::DRAFT)
			->byID($subclass2->ID);
		$this->assertEquals('Subclass 2 - v1', $subclass2Draft->Title);
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2 - v1'],
				['Title' => 'Attachment 3 - v1'],
				['Title' => 'Attachment 4 - v1'],
				['Title' => 'Attachment 5 - v1'],
				['Title' => 'Related Many 4 - v1'],
			],
			$subclass2Draft->findOwned(true)
		);

		// Check rolling forward to a later version
		$this->sleep(1);
		$subclass2->doRollbackTo($versions[3]);
		/** @var VersionedOwnershipTest_Subclass $subclass2Draft */
		$subclass2Draft = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::DRAFT)
			->byID($subclass2->ID);
		$this->assertEquals('Subclass 2 - v1 - v2 - v3', $subclass2Draft->Title);
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2 - v1 - v2 - v3'],
				['Title' => 'Attachment 3 - v1 - v2 - v3'],
				['Title' => 'Attachment 4 - v1 - v2 - v3'],
				['Title' => 'Attachment 5 - v1 - v2 - v3'],
				['Title' => 'Related Many 4 - v1 - v2 - v3'],
			],
			$subclass2Draft->findOwned(true)
		);

		// And rolling back one version
		$this->sleep(1);
		$subclass2->doRollbackTo($versions[2]);
		/** @var VersionedOwnershipTest_Subclass $subclass2Draft */
		$subclass2Draft = Versioned::get_by_stage('VersionedOwnershipTest_Subclass', Versioned::DRAFT)
			->byID($subclass2->ID);
		$this->assertEquals('Subclass 2 - v1 - v2', $subclass2Draft->Title);
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2 - v1 - v2'],
				['Title' => 'Attachment 3 - v1 - v2'],
				['Title' => 'Attachment 4 - v1 - v2'],
				['Title' => 'Attachment 5 - v1 - v2'],
				['Title' => 'Related Many 4 - v1 - v2'],
			],
			$subclass2Draft->findOwned(true)
		);
	}

	/**
	 * Test that you can find owners without owned_by being defined explicitly
	 */
	public function testInferedOwners() {
		// Make sure findOwned() works
		/** @var VersionedOwnershipTest_Page $page1 */
		$page1 = $this->objFromFixture('VersionedOwnershipTest_Page', 'page1_published');
		/** @var VersionedOwnershipTest_Page $page2 */
		$page2 = $this->objFromFixture('VersionedOwnershipTest_Page', 'page2_published');
		$this->assertDOSEquals(
			[
				['Title' => 'Banner 1'],
				['Title' => 'Image 1'],
				['Title' => 'Custom 1'],
			],
			$page1->findOwned()
		);
		$this->assertDOSEquals(
			[
				['Title' => 'Banner 2'],
				['Title' => 'Banner 3'],
				['Title' => 'Image 1'],
				['Title' => 'Image 2'],
				['Title' => 'Custom 2'],
			],
			$page2->findOwned()
		);

		// Check that findOwners works
		/** @var VersionedOwnershipTest_Image $image1 */
		$image1 = $this->objFromFixture('VersionedOwnershipTest_Image', 'image1_published');
		/** @var VersionedOwnershipTest_Image $image2 */
		$image2 = $this->objFromFixture('VersionedOwnershipTest_Image', 'image2_published');

		$this->assertDOSEquals(
			[
				['Title' => 'Banner 1'],
				['Title' => 'Banner 2'],
				['Title' => 'Page 1'],
				['Title' => 'Page 2'],
			],
			$image1->findOwners()
		);
		$this->assertDOSEquals(
			[
				['Title' => 'Banner 1'],
				['Title' => 'Banner 2'],
			],
			$image1->findOwners(false)
		);
		$this->assertDOSEquals(
			[
				['Title' => 'Banner 3'],
				['Title' => 'Page 2'],
			],
			$image2->findOwners()
		);
		$this->assertDOSEquals(
			[
				['Title' => 'Banner 3'],
			],
			$image2->findOwners(false)
		);

		// Test custom relation can findOwners()
		/** @var VersionedOwnershipTest_CustomRelation $custom1 */
		$custom1 = $this->objFromFixture('VersionedOwnershipTest_CustomRelation', 'custom1_published');
		$this->assertDOSEquals(
			[['Title' => 'Page 1']],
			$custom1->findOwners()
		);

	}

}

/**
 * @mixin Versioned
 */
class VersionedOwnershipTest_Object extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Content' => 'Text',
	);
}

/**
 * Object which:
 * - owns a has_one object
 * - owns has_many objects
 */
class VersionedOwnershipTest_Subclass extends VersionedOwnershipTest_Object implements TestOnly {
	private static $db = array(
		'Description' => 'Text',
	);

	private static $has_one = array(
		'Related' => 'VersionedOwnershipTest_Related',
	);

	private static $has_many = array(
		'Banners' => 'VersionedOwnershipTest_RelatedMany'
	);

	private static $owns = array(
		'Related',
		'Banners',
	);
}

/**
 * Object which:
 * - owned by has_many objects
 * - owns many_many Objects
 *
 * @mixin Versioned
 */
class VersionedOwnershipTest_Related extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $has_many = array(
		'Parents' => 'VersionedOwnershipTest_Subclass.Related',
	);

	private static $owned_by = array(
		'Parents',
	);

	private static $many_many = array(
		// Note : Currently unversioned, take care
		'Attachments' => 'VersionedOwnershipTest_Attachment',
	);

	private static $owns = array(
		'Attachments',
	);
}

/**
 * Object which is owned by a has_one object
 *
 * @mixin Versioned
 */
class VersionedOwnershipTest_RelatedMany extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $has_one = array(
		'Page' => 'VersionedOwnershipTest_Subclass'
	);

	private static $owned_by = array(
		'Page'
	);
}

/**
 * @mixin Versioned
 */
class VersionedOwnershipTest_Attachment extends DataObject implements TestOnly {

	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $belongs_many_many = array(
		'AttachedTo' => 'VersionedOwnershipTest_Related.Attachments'
	);

	private static $owned_by = array(
		'AttachedTo'
	);
}

/**
 * Page which owns a lits of banners
 *
 * @mixin Versioned
 */
class VersionedOwnershipTest_Page extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $many_many = array(
		'Banners' => 'VersionedOwnershipTest_Banner',
	);

	private static $owns = array(
		'Banners',
		'Custom'
	);

	/**
	 * All custom objects with the same number. E.g. 'Page 1' owns 'Custom 1'
	 *
	 * @return DataList
	 */
	public function Custom() {
		$title = str_replace('Page', 'Custom', $this->Title);
		return VersionedOwnershipTest_CustomRelation::get()
			->filter('Title', $title);
	}
}

/**
 * Banner which doesn't declare its belongs_many_many, but owns an Image
 *
 * @mixin Versioned
 */
class VersionedOwnershipTest_Banner extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $has_one = array(
		'Image' => 'VersionedOwnershipTest_Image',
	);

	private static $owns = array(
		'Image',
	);
}


/**
 * Object which is owned via a custom PHP method rather than DB relation
 *
 * @mixin Versioned
 */
class VersionedOwnershipTest_CustomRelation extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $owned_by = array(
		'Pages'
	);

	/**
	 * All pages with the same number. E.g. 'Page 1' owns 'Custom 1'
	 *
	 * @return DataList
	 */
	public function Pages() {
		$title = str_replace('Custom', 'Page', $this->Title);
		return VersionedOwnershipTest_Page::get()->filter('Title', $title);
	}

}

/**
 * Simple versioned dataobject
 *
 * @mixin Versioned
 */
class VersionedOwnershipTest_Image extends DataObject implements TestOnly {
	private static $extensions = array(
		'SilverStripe\\ORM\\Versioning\\Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
	);
}
