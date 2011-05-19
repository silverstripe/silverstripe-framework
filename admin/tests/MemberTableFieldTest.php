<?php
/**
 * @package cms
 * @subpackage tests
 */

class MemberTableFieldTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/admin/tests/MemberTableFieldTest.yml';
	
	
	function testLimitsToMembersInGroup() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member2 = $this->objFromFixture('Member', 'member2');
		$member3 = $this->objFromFixture('Member', 'member3');
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$tf = new MemberTableField(
			$this,
			"Members",
			$group1
		);
		$members = $tf->sourceItems();
		
		$this->assertContains($member1->ID, $members->column('ID'),
			'Members in the associated group are listed'
		);
		$this->assertContains($member2->ID, $members->column('ID'),
			'Members in children groups are listed as well'
		);
		$this->assertNotContains($member3->ID, $members->column('ID'),
			'Members in other groups are filtered out'
		);
	}
	
	function testShowsAllMembersWithoutGroupParameter() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member2 = $this->objFromFixture('Member', 'member2');
		$member3 = $this->objFromFixture('Member', 'member3');
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$tf = new MemberTableField(
			$this,
			"Members"
			// no group assignment
		);
		$members = $tf->sourceItems();
		
		$this->assertContains($member1->ID, $members->column('ID'),
			'Members in the associated group are listed'
		);
		$this->assertContains($member2->ID, $members->column('ID'),
			'Members in children groups are listed as well'
		);
		$this->assertContains($member3->ID, $members->column('ID'),
			'Members in other groups are listed'
		);
	}
	
	function testDeleteWithGroupOnlyDeletesRelation() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$response = $this->get('MemberTableFieldTest_Controller');
		$token = SecurityToken::inst();
		$url = sprintf('MemberTableFieldTest_Controller/Form/field/Members/item/%d/delete/?usetestmanifest=1', $member1->ID);
		$url = $token->addToUrl($url);
		$response = $this->get($url);
		
		$group1->flushCache();
		
		$this->assertNotContains($member1->ID, $group1->Members()->column('ID'),
			'Member relation to group is removed'
		);
		$this->assertType(
			'DataObject',
			DataObject::get_by_id('Member', $member1->ID),
			'Member record still exists'
		);
	}
	
	function testDeleteWithoutGroupDeletesFromDatabase() {
		$member1 = $this->objFromFixture('Member', 'member1');
		$member1ID = $member1->ID;
		$group1 = $this->objFromFixture('Group', 'group1');
		
		$response = $this->get('MemberTableFieldTest_Controller');
		$token = SecurityToken::inst();
		$url = sprintf('MemberTableFieldTest_Controller/FormNoGroup/field/Members/item/%d/delete/?usetestmanifest=1', $member1->ID);
		$url = $token->addToUrl($url);
		$response = $this->get($url);
		
		$group1->flushCache();
		
		$this->assertNotContains($member1->ID, $group1->Members()->column('ID'),
			'Member relation to group is removed'
		);
		DataObject::flush_and_destroy_cache();
		$this->assertFalse(
			DataObject::get_by_id('Member', $member1ID),
			'Member record is removed from database'
		);
	}
}

class MemberTableFieldTest_Controller extends Controller implements TestOnly {
	
	protected $template = 'BlankPage';
	
	function Link($action = null) {
		return Controller::join_links('MemberTableFieldTest_Controller', $action);
	}
	
	function Form() {
		$group1 = DataObject::get_one('Group', '"Code" = \'group1\'');
		return new Form(
			$this,
			'FormNoGroup',
			new FieldSet(new MemberTableField($this, "Members", $group1)),
			new FieldSet(new FormAction('submit'))
		);
	}

	function FormNoGroup() {
		$tf = new MemberTableField(
			$this,
			"Members"
			// no group
		);
		
		return new Form(
			$this,
			'FormNoGroup',
			new FieldSet(new MemberTableField($this, "Members")),
			new FieldSet(new FormAction('submit'))
		);
	}
	
}