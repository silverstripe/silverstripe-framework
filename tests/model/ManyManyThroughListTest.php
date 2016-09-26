<?php

use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ManyManyThroughList;
use SilverStripe\ORM\Versioning\Versioned;

class ManyManyThroughListTest extends SapphireTest
{
	protected static $fixture_file = 'ManyManyThroughListTest.yml';

	protected $extraDataObjects = [
		ManyManyThroughListTest_Item::class,
		ManyManyThroughListTest_JoinObject::class,
		ManyManyThroughListTest_Object::class,
		ManyManyThroughListTest_VersionedItem::class,
		ManyManyThroughListTest_VersionedJoinObject::class,
		ManyManyThroughListTest_VersionedObject::class,
	];

	public function testSelectJoin() {
		/** @var ManyManyThroughListTest_Object $parent */
		$parent = $this->objFromFixture(ManyManyThroughListTest_Object::class, 'parent1');
		$this->assertDOSEquals(
			[
				['Title' => 'item 1'],
				['Title' => 'item 2']
			],
			$parent->Items()
		);
		// Check filters on list work
		$item1 = $parent->Items()->filter('Title', 'item 1')->first();
		$this->assertNotNull($item1);
		$this->assertNotNull($item1->getJoin());
		$this->assertEquals('join 1', $item1->getJoin()->Title);

		// Check filters on list work
		$item2 = $parent->Items()->filter('Title', 'item 2')->first();
		$this->assertNotNull($item2);
		$this->assertNotNull($item2->getJoin());
		$this->assertEquals('join 2', $item2->getJoin()->Title);

		// To filter on join table need to use some raw sql
		$item2 = $parent->Items()->where(['"Join"."Title"' => 'join 2'])->first();
		$this->assertNotNull($item2);
		$this->assertEquals('item 2', $item2->Title);
		$this->assertNotNull($item2->getJoin());
		$this->assertEquals('join 2', $item2->getJoin()->Title);
	}

	public function testAdd() {
		/** @var ManyManyThroughListTest_Object $parent */
		$parent = $this->objFromFixture(ManyManyThroughListTest_Object::class, 'parent1');
		$newItem = new ManyManyThroughListTest_Item();
		$newItem->Title = 'my new item';
		$newItem->write();
		$parent->Items()->add($newItem, ['Title' => 'new join record']);

		// Check select
		$newItem = $parent->Items()->filter(['Title' => 'my new item'])->first();
		$this->assertNotNull($newItem);
		$this->assertEquals('my new item', $newItem->Title);
		$this->assertNotNull($newItem->getJoin());
		$this->assertEquals('new join record', $newItem->getJoin()->Title);
	}

	public function testRemove() {
		/** @var ManyManyThroughListTest_Object $parent */
		$parent = $this->objFromFixture(ManyManyThroughListTest_Object::class, 'parent1');
		$this->assertDOSEquals(
			[
				['Title' => 'item 1'],
				['Title' => 'item 2']
			],
			$parent->Items()
		);
		$item1 = $parent->Items()->filter(['Title' => 'item 1'])->first();
		$parent->Items()->remove($item1);
		$this->assertDOSEquals(
			[['Title' => 'item 2']],
			$parent->Items()
		);
	}

	public function testPublishing() {
		/** @var ManyManyThroughListTest_VersionedObject $draftParent */
		$draftParent = $this->objFromFixture(ManyManyThroughListTest_VersionedObject::class, 'parent1');
		$draftParent->publishRecursive();

		// Modify draft stage
		$item1 = $draftParent->Items()->filter(['Title' => 'versioned item 1'])->first();
		$item1->Title = 'new versioned item 1';
		$item1->getJoin()->Title = 'new versioned join 1';
		$item1->write(false, false, false, true); // Write joined components
		$draftParent->Title = 'new versioned title';
		$draftParent->write();

		// Check owned objects on stage
		$draftOwnedObjects = $draftParent->findOwned(true);
		$this->assertDOSEquals(
			[
				['Title' => 'new versioned join 1'],
				['Title' => 'versioned join 2'],
				['Title' => 'new versioned item 1'],
				['Title' => 'versioned item 2'],
			],
			$draftOwnedObjects
		);

		// Check live record is still old values
		// This tests that both the join table and many_many tables
		// inherit the necessary query parameters from the parent object.
		/** @var ManyManyThroughListTest_VersionedObject $liveParent */
		$liveParent = Versioned::get_by_stage(
			ManyManyThroughListTest_VersionedObject::class,
			Versioned::LIVE
		)->byID($draftParent->ID);
		$liveOwnedObjects = $liveParent->findOwned(true);
		$this->assertDOSEquals(
			[
				['Title' => 'versioned join 1'],
				['Title' => 'versioned join 2'],
				['Title' => 'versioned item 1'],
				['Title' => 'versioned item 2'],
			],
			$liveOwnedObjects
		);

		// Publish draft changes
		$draftParent->publishRecursive();
		$liveParent = Versioned::get_by_stage(
			ManyManyThroughListTest_VersionedObject::class,
			Versioned::LIVE
		)->byID($draftParent->ID);
		$liveOwnedObjects = $liveParent->findOwned(true);
		$this->assertDOSEquals(
			[
				['Title' => 'new versioned join 1'],
				['Title' => 'versioned join 2'],
				['Title' => 'new versioned item 1'],
				['Title' => 'versioned item 2'],
			],
			$liveOwnedObjects
		);
	}
}

/**
 * Basic parent object
 *
 * @property string $Title
 * @method ManyManyThroughList Items()
 */
class ManyManyThroughListTest_Object extends DataObject implements TestOnly
{
	private static $db = [
		'Title' => 'Varchar'
	];

	private static $many_many = [
		'Items' => [
			'through' => ManyManyThroughListTest_JoinObject::class,
			'from' => 'Parent',
			'to' => 'Child',
		]
	];
}

/**
 * @property string $Title
 * @method ManyManyThroughListTest_Object Parent()
 * @method ManyManyThroughListTest_Item Child()
 */
class ManyManyThroughListTest_JoinObject extends DataObject implements TestOnly
{
	private static $db = [
		'Title' => 'Varchar'
	];

	private static $has_one = [
		'Parent' => ManyManyThroughListTest_Object::class,
		'Child' => ManyManyThroughListTest_Item::class,
	];
}

/**
 * @property string $Title
 * @method ManyManyThroughList Objects()
 */
class ManyManyThroughListTest_Item extends DataObject implements TestOnly
{
	private static $db = [
		'Title' => 'Varchar'
	];

	private static $belongs_many_many = [
		'Objects' => 'ManyManyThroughListTest_Object.Items'
	];
}

/**
 * Basic parent object
 *
 * @property string $Title
 * @method ManyManyThroughList Items()
 * @mixin Versioned
 */
class ManyManyThroughListTest_VersionedObject extends DataObject implements TestOnly
{
	private static $db = [
		'Title' => 'Varchar'
	];

	private static $extensions = [
		Versioned::class
	];

	private static $owns = [
		'Items' // Should automatically own both mapping and child records
	];

	private static $many_many = [
		'Items' => [
			'through' => ManyManyThroughListTest_VersionedJoinObject::class,
			'from' => 'Parent',
			'to' => 'Child',
		]
	];
}

/**
 * @property string $Title
 * @method ManyManyThroughListTest_VersionedObject Parent()
 * @method ManyManyThroughListTest_VersionedItem Child()
 * @mixin Versioned
 */
class ManyManyThroughListTest_VersionedJoinObject extends DataObject implements TestOnly
{
	private static $db = [
		'Title' => 'Varchar'
	];

	private static $extensions = [
		Versioned::class
	];

	private static $has_one = [
		'Parent' => ManyManyThroughListTest_VersionedObject::class,
		'Child' => ManyManyThroughListTest_VersionedItem::class,
	];
}

/**
 * @property string $Title
 * @method ManyManyThroughList Objects()
 * @mixin Versioned
 */
class ManyManyThroughListTest_VersionedItem extends DataObject implements TestOnly
{
	private static $db = [
		'Title' => 'Varchar'
	];

	private static $extensions = [
		Versioned::class
	];

	private static $belongs_many_many = [
		'Objects' => 'ManyManyThroughListTest_VersionedObject.Items'
	];
}

