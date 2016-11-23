<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\Session;
use SilverStripe\Security\Tests\GroupTest\TestMember;
use ReflectionMethod;

class GroupTest extends FunctionalTest {

	protected static $fixture_file = 'GroupTest.yml';

	protected $extraDataObjects = [
		TestMember::class
	];

	public function testGroupCodeDefaultsToTitle() {
		$g1 = new Group();
		$g1->Title = "My Title";
		$g1->write();
		$this->assertEquals('my-title', $g1->Code, 'Custom title gets converted to code if none exists already');

		$g2 = new Group();
		$g2->Title = "My Title";
		$g2->Code = "my-code";
		$g2->write();
		$this->assertEquals('my-code', $g2->Code, 'Custom attributes are not overwritten by Title field');

		$g3 = new Group();
		$g3->Title = _t('SecurityAdmin.NEWGROUP',"New Group");
		$g3->write();
		$this->assertNull($g3->Code, 'Default title doesnt trigger attribute setting');
	}

	public function testMemberGroupRelationForm() {
		Session::set('loggedInAs', $this->idFromFixture(TestMember::class, 'admin'));

		$adminGroup = $this->objFromFixture(Group::class, 'admingroup');
		$parentGroup = $this->objFromFixture(Group::class, 'parentgroup');
		$childGroup = $this->objFromFixture(Group::class, 'childgroup');

		// Test single group relation through checkboxsetfield
		/** @skipUpgrade */
		$form = new GroupTest\MemberForm($this, 'Form');
		$member = $this->objFromFixture(TestMember::class, 'admin');
		$form->loadDataFrom($member);
		$checkboxSetField = $form->Fields()->fieldByName('Groups');
		$checkboxSetField->setValue(array(
			$adminGroup->ID => $adminGroup->ID, // keep existing relation
			$parentGroup->ID => $parentGroup->ID, // add new relation
		));
		$form->saveInto($member);
		$updatedGroups = $member->Groups();

		$this->assertEquals(2, count($updatedGroups->column()),
			"Adding a toplevel group works"
		);
		$this->assertContains($adminGroup->ID, $updatedGroups->column('ID'));
		$this->assertContains($parentGroup->ID, $updatedGroups->column('ID'));

		// Test unsetting relationship
		$form->loadDataFrom($member);
		$checkboxSetField = $form->Fields()->fieldByName('Groups');
		$checkboxSetField->setValue(array(
			$adminGroup->ID => $adminGroup->ID, // keep existing relation
			//$parentGroup->ID => $parentGroup->ID, // remove previously set relation
		));
		$form->saveInto($member);
		$member->flushCache();
		$updatedGroups = $member->Groups();
		$this->assertEquals(1, count($updatedGroups->column()),
			"Removing a previously added toplevel group works"
		);
		$this->assertContains($adminGroup->ID, $updatedGroups->column('ID'));

		// Test adding child group

	}

	public function testUnsavedGroups() {
		$member = $this->objFromFixture(TestMember::class, 'admin');
		$group = new Group();

		// Can save user to unsaved group
		$group->Members()->add($member);
		$this->assertEquals(array($member->ID), array_values($group->Members()->getIDList()));

		// Persists after writing to DB
		$group->write();
		$group = Group::get()->byID($group->ID);
		$this->assertEquals(array($member->ID), array_values($group->Members()->getIDList()));
	}

	public function testCollateAncestorIDs() {
		$parentGroup = $this->objFromFixture(Group::class, 'parentgroup');
		$childGroup = $this->objFromFixture(Group::class, 'childgroup');
		$orphanGroup = new Group();
		$orphanGroup->ParentID = 99999;
		$orphanGroup->write();

		$this->assertEquals(1, count($parentGroup->collateAncestorIDs()),
			'Root node only contains itself'
		);
		$this->assertContains($parentGroup->ID, $parentGroup->collateAncestorIDs());

		$this->assertEquals(2, count($childGroup->collateAncestorIDs()),
			'Contains parent nodes, with child node first'
		);
		$this->assertContains($parentGroup->ID, $childGroup->collateAncestorIDs());
		$this->assertContains($childGroup->ID, $childGroup->collateAncestorIDs());

		$this->assertEquals(1, count($orphanGroup->collateAncestorIDs()),
			'Orphaned nodes dont contain invalid parent IDs'
		);
		$this->assertContains($orphanGroup->ID, $orphanGroup->collateAncestorIDs());
	}

	public function testDelete() {
		$group = $this->objFromFixture(Group::class, 'parentgroup');
		$groupID = $group->ID;
		$childGroupID = $this->idFromFixture(Group::class, 'childgroup');
		$group->delete();

		$this->assertEquals(0, DataObject::get(Group::class, "\"ID\" = {$groupID}")->Count(),
			'Group is removed');
		$this->assertEquals(0, DataObject::get('SilverStripe\\Security\\Permission', "\"GroupID\" = {$groupID}")->Count(),
			'Permissions removed along with the group');
		$this->assertEquals(0, DataObject::get(Group::class, "\"ParentID\" = {$groupID}")->Count(),
			'Child groups are removed');
		$this->assertEquals(0, DataObject::get(Group::class, "\"ParentID\" = {$childGroupID}")->Count(),
			'Grandchild groups are removed');
	}

	public function testValidatesPrivilegeLevelOfParent() {
		$nonAdminUser = $this->objFromFixture(TestMember::class, 'childgroupuser');
		$adminUser = $this->objFromFixture(TestMember::class, 'admin');
		$nonAdminGroup = $this->objFromFixture(Group::class, 'childgroup');
		$adminGroup = $this->objFromFixture(Group::class, 'admingroup');

		$nonAdminValidateMethod = new ReflectionMethod($nonAdminGroup, 'validate');
		$nonAdminValidateMethod->setAccessible(true);

		// Making admin group parent of a non-admin group, effectively expanding is privileges
		$nonAdminGroup->ParentID = $adminGroup->ID;

		$this->logInWithPermission('APPLY_ROLES');
		$result = $nonAdminValidateMethod->invoke($nonAdminGroup);
		$this->assertFalse(
			$result->valid(),
			'Members with only APPLY_ROLES can\'t assign parent groups with direct ADMIN permissions'
		);

		$this->logInWithPermission('ADMIN');
		$result = $nonAdminValidateMethod->invoke($nonAdminGroup);
		$this->assertTrue(
			$result->valid(),
			'Members with ADMIN can assign parent groups with direct ADMIN permissions'
		);
		$nonAdminGroup->write();
		$newlyAdminGroup = $nonAdminGroup;

		$this->logInWithPermission('ADMIN');
		$inheritedAdminGroup = $this->objFromFixture(Group::class, 'group1');
		$inheritedAdminMethod = new ReflectionMethod($inheritedAdminGroup, 'validate');
		$inheritedAdminMethod->setAccessible(true);
		$inheritedAdminGroup->ParentID = $adminGroup->ID;
		$inheritedAdminGroup->write(); // only works with ADMIN login

		$this->logInWithPermission('APPLY_ROLES');
		$result = $inheritedAdminMethod->invoke($nonAdminGroup);
		$this->assertFalse(
			$result->valid(),
			'Members with only APPLY_ROLES can\'t assign parent groups with inherited ADMIN permission'
		);
	}

}
