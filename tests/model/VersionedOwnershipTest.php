<?php

/**
 * Tests ownership API of versioned DataObjects
 */
class VersionedOwnershipTest extends SapphireTest {

	protected $extraDataObjects = array(
		'VersionedOwnershipTest_Object',
		'VersionedOwnershipTest_Subclass',
		'VersionedOwnershipTest_Related',
		'VersionedOwnershipTest_Attachment',
	);

	protected static $fixture_file = 'VersionedOwnershipTest.yml';

	public function setUp()
	{
		parent::setUp();

		Versioned::reading_stage('Stage');

		// Automatically publish any object named *_published
		foreach($this->getFixtureFactory()->getFixtures() as $class => $fixtures) {
			foreach($fixtures as $name => $id) {
				if(stripos($name, '_published') !== false) {
					/** @var Versioned|DataObject $object */
					$object = DataObject::get($class)->byID($id);
					$object->publish('Stage', 'Live');
				}
			}
		}
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
			],
			$subclass1->findOwned()
		);

		// Non-recursive search
		$this->assertDOSEquals(
			[
				['Title' => 'Related 1'],
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
			],
			$subclass2->findOwned()
		);

		// Non-recursive search
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2']
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
		$subclass2Stage = \Versioned::get_by_stage('VersionedOwnershipTest_Subclass', 'Stage')->byID($subclass2ID);
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2 Modified'],
				['Title' => 'Attachment 3 Modified'],
				['Title' => 'Attachment 5'],
			],
			$subclass2Stage->findOwned()
		);

		// Non-recursive
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2 Modified'],
			],
			$subclass2Stage->findOwned(false)
		);

		// Live records are unchanged
		/** @var VersionedOwnershipTest_Subclass $subclass2Live */
		$subclass2Live = \Versioned::get_by_stage('VersionedOwnershipTest_Subclass', 'Live')->byID($subclass2ID);
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2'],
				['Title' => 'Attachment 3'],
				['Title' => 'Attachment 4'],
				['Title' => 'Attachment 5'],
			],
			$subclass2Live->findOwned()
		);

		// Test non-recursive
		$this->assertDOSEquals(
			[
				['Title' => 'Related 2'],
			],
			$subclass2Live->findOwned(false)
		);
	}
}

/**
 * @mixin Versioned
 */
class VersionedOwnershipTest_Object extends DataObject implements TestOnly {
	private static $extensions = array(
		'Versioned',
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Content' => 'Text',
	);
}

class VersionedOwnershipTest_Subclass extends VersionedOwnershipTest_Object implements TestOnly {
	private static $db = array(
		'Description' => 'Text',
	);

	private static $has_one = array(
		'Related' => 'VersionedOwnershipTest_Related',
	);

	private static $owns = array(
		'Related',
	);
}

/**
 * @mixin Versioned
 */
class VersionedOwnershipTest_Related extends DataObject implements TestOnly {
	private static $extensions = array(
		'Versioned',
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
 * @mixin Versioned
 */
class VersionedOwnershipTest_Attachment extends DataObject implements TestOnly {

	private static $extensions = array(
		'Versioned',
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
