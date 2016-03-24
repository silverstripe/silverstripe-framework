<?php

/**
 * Provides a set of targettable permissions for tested models
 *
 * @mixin Versioned
 * @mixin DataObject
 */
trait ChangeSetTest_Permissions {
	public function canEdit($member = null) {
		return $this->can(__FUNCTION__, $member);
	}

	public function canDelete($member = null) {
		return $this->can(__FUNCTION__, $member);
	}

	public function canCreate($member = null, $context = array()) {
		return $this->can(__FUNCTION__, $member, $context);
	}

	public function canPublish($member = null, $context = array()) {
		return $this->can(__FUNCTION__, $member, $context);
	}

	public function canUnpublish($member = null, $context = array()) {
		return $this->can(__FUNCTION__, $member, $context);
	}

	public function can($perm, $member = null, $context = array()) {
		$perms = [
			"PERM_{$perm}",
			'CAN_ALL',
		];
		return Permission::checkMember($member, $perms);
	}
}

/**
 * @mixin Versioned
 */
class ChangeSetTest_Base extends DataObject implements TestOnly {
	use ChangeSetTest_Permissions;

	private static $db = [
		'Foo' => 'Int',
	];

	private static $has_many = [
		'Mids' => 'ChangeSetTest_Mid',
	];

	private static $owns = [
		'Mids',
	];

	private static $extensions = [
		"Versioned",
	];
}

/**
 * @mixin Versioned
 */
class ChangeSetTest_Mid extends DataObject implements TestOnly {
	use ChangeSetTest_Permissions;

	private static $db = [
		'Bar' => 'Int',
	];

	private static $has_one = [
		'Base' => 'ChangeSetTest_Base',
		'End' => 'ChangeSetTest_End',
	];

	private static $owns = [
		'End',
	];

	private static $extensions = [
		"Versioned",
	];
}

/**
 * @mixin Versioned
 */
class ChangeSetTest_End extends DataObject implements TestOnly {
	use ChangeSetTest_Permissions;

	private static $db = [
		'Baz' => 'Int',
	];

	private static $extensions = [
		"Versioned",
	];
}

/**
 * Test {@see ChangeSet} and {@see ChangeSetItem} models
 */
class ChangeSetTest extends SapphireTest {

	protected static $fixture_file = 'ChangeSetTest.yml';

	protected $extraDataObjects = [
		'ChangeSetTest_Base',
		'ChangeSetTest_Mid',
		'ChangeSetTest_End',
	];

	/**
	 * Automatically publish all objects
	 */
	protected function publishAllFixtures() {
		$this->logInWithPermission('ADMIN');
		foreach($this->fixtureFactory->getFixtures() as $class => $fixtures) {
			foreach ($fixtures as $handle => $id) {
				$this->objFromFixture($class, $handle)->doPublish();
			}
		}
	}

	/**
	 * Check that the changeset includes the given items
	 *
	 * @param ChangeSet $cs
	 * @param array $match Array of object fixture keys with change type values
	 */
	protected function assertChangeSetLooksLike($cs, $match) {
		$items = $cs->Changes()->toArray();

		foreach($match as $key => $mode) {
			list($class, $identifier) = explode('.', $key);
			$object = $this->objFromFixture($class, $identifier);

			foreach($items as $i => $item) {
				if ($item->ObjectClass == $object->ClassName && $item->ObjectID == $object->ID && $item->Added == $mode) {
					unset($items[$i]);
					continue 2;
				}
			}

			throw new PHPUnit_Framework_ExpectationFailedException(
				'Change set didn\'t include expected item',
				new \SebastianBergmann\Comparator\ComparisonFailure(array('Class' => $class, 'ID' => $object->ID, 'Added' => $mode), null, "$key => $mode", '')
			);
		}

		if (count($items)) {
			$extra = [];
			foreach ($items as $item) $extra[] = ['Class' => $item->ObjectClass, 'ID' => $item->ObjectID, 'Added' => $item->Added, 'ChangeType' => $item->getChangeType()];
			throw new PHPUnit_Framework_ExpectationFailedException(
				'Change set included items that weren\'t expected',
				new \SebastianBergmann\Comparator\ComparisonFailure(array(), $extra, '', print_r($extra, true))
			);
		}
	}

	public function testRepeatedSyncIsNOP() {
		$this->publishAllFixtures();

		$cs = new ChangeSet();
		$cs->write();

		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$cs->addObject($base);

		$cs->sync();
		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);

		$cs->sync();
		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);
	}

	public function testSync() {
		$this->publishAllFixtures();

		$cs = new ChangeSet();
		$cs->write();

		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');

		$cs->addObject($base);
		$cs->sync();

		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);

		$end = $this->objFromFixture('ChangeSetTest_End', 'end1');
		$end->Baz = 3;
		$end->write();

		$cs->sync();

		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY,
			'ChangeSetTest_End.end1' => ChangeSetItem::IMPLICITLY
		]);

		$endItem = $cs->Changes()->filter('ObjectClass', 'ChangeSetTest_End')->first();

		$this->assertEquals(
			'ChangeSetTest_Base.'.$base->ID,
			$endItem->ReferencedBy
		);
	}

	/**
	 * Test that sync includes implicit items
	 */
	public function testIsSynced() {
		$this->publishAllFixtures();

		$cs = new ChangeSet();
		$cs->write();

		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$cs->addObject($base);

		$cs->sync();
		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);
		$this->assertTrue($cs->isSynced());

		$end = $this->objFromFixture('ChangeSetTest_End', 'end1');
		$end->Baz = 3;
		$end->write();
		$this->assertFalse($cs->isSynced());

		$cs->sync();

		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY,
			'ChangeSetTest_End.end1' => ChangeSetItem::IMPLICITLY
		]);
		$this->assertTrue($cs->isSynced());
	}


	public function testCanPublish() {
		// Create changeset containing all items (unpublished)
		$this->logInWithPermission('ADMIN');
		$changeSet = new ChangeSet();
		$changeSet->write();
		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$changeSet->addObject($base);
		$changeSet->sync();
		$this->assertEquals(5, $changeSet->Changes()->count());

		// Test un-authenticated user cannot publish
		Session::clear("loggedInAs");
		$this->assertFalse($changeSet->canPublish());

		// User with only one of the necessary permissions cannot publish
		$this->logInWithPermission('CMS_ACCESS_CampaignAdmin');
		$this->assertFalse($changeSet->canPublish());
		$this->logInWithPermission('PERM_canPublish');
		$this->assertFalse($changeSet->canPublish());

		// Test user with the necessary minimum permissions can login
		$this->logInWithPermission([
			'CMS_ACCESS_CampaignAdmin',
			'PERM_canPublish'
		]);
		$this->assertTrue($changeSet->canPublish());
	}

	public function testCanRevert() {
		$this->markTestSkipped("Requires ChangeSet::publish to be implemented first");
	}

	public function testCanEdit() {
		// Create changeset containing all items (unpublished)
		$this->logInWithPermission('ADMIN');
		$changeSet = new ChangeSet();
		$changeSet->write();
		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$changeSet->addObject($base);
		$changeSet->sync();
		$this->assertEquals(5, $changeSet->Changes()->count());

		// Check canEdit
		Session::clear("loggedInAs");
		$this->assertFalse($changeSet->canEdit());
		$this->logInWithPermission('SomeWrongPermission');
		$this->assertFalse($changeSet->canEdit());
		$this->logInWithPermission('CMS_ACCESS_CampaignAdmin');
		$this->assertTrue($changeSet->canEdit());
	}

	public function testCanCreate() {
		// Check canCreate
		Session::clear("loggedInAs");
		$this->assertFalse(ChangeSet::singleton()->canCreate());
		$this->logInWithPermission('SomeWrongPermission');
		$this->assertFalse(ChangeSet::singleton()->canCreate());
		$this->logInWithPermission('CMS_ACCESS_CampaignAdmin');
		$this->assertTrue(ChangeSet::singleton()->canCreate());
	}

	public function testCanDelete() {
		// Create changeset containing all items (unpublished)
		$this->logInWithPermission('ADMIN');
		$changeSet = new ChangeSet();
		$changeSet->write();
		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$changeSet->addObject($base);
		$changeSet->sync();
		$this->assertEquals(5, $changeSet->Changes()->count());

		// Check canDelete
		Session::clear("loggedInAs");
		$this->assertFalse($changeSet->canDelete());
		$this->logInWithPermission('SomeWrongPermission');
		$this->assertFalse($changeSet->canDelete());
		$this->logInWithPermission('CMS_ACCESS_CampaignAdmin');
		$this->assertTrue($changeSet->canDelete());
	}

	public function testCanView() {
		// Create changeset containing all items (unpublished)
		$this->logInWithPermission('ADMIN');
		$changeSet = new ChangeSet();
		$changeSet->write();
		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$changeSet->addObject($base);
		$changeSet->sync();
		$this->assertEquals(5, $changeSet->Changes()->count());

		// Check canView
		Session::clear("loggedInAs");
		$this->assertFalse($changeSet->canView());
		$this->logInWithPermission('SomeWrongPermission');
		$this->assertFalse($changeSet->canView());
		$this->logInWithPermission('CMS_ACCESS_CampaignAdmin');
		$this->assertTrue($changeSet->canView());
	}

}
