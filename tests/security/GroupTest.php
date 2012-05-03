<?php
/**
 * @package framework
 * @subpackage tests
 */
class GroupTest extends FunctionalTest {

	static $fixture_file = 'GroupTest.yml';
	
	function testGroupCodeDefaultsToTitle() {
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
	
	/**
	 * Test the Group::map() function
	 */
	function testGroupMap() {
		// 2.4 only
		$originalDeprecation = Deprecation::dump_settings();
		Deprecation::notification_version('2.4');
		
		/* Group::map() returns an SQLMap object implementing iterator.  You can use foreach to get ID-Title pairs. */
		
		// We will iterate over the map and build mapOuput to more easily call assertions on the result.
		$map = Group::map();
		$mapOutput = $map->toArray();
		
		$group1 = $this->objFromFixture('Group', 'group1');
		$group2 = $this->objFromFixture('Group', 'group2');

		/* We have added 2 groups to our fixture.  They should both appear in $mapOutput. */
		$this->assertEquals($mapOutput[$group1->ID], $group1->Title);
		$this->assertEquals($mapOutput[$group2->ID], $group2->Title);

		Deprecation::restore_settings($originalDeprecation);
	}
	
	function testMemberGroupRelationForm() {
		Session::set('loggedInAs', $this->idFromFixture('GroupTest_Member', 'admin'));
		
	      $adminGroup = $this->objFromFixture('Group', 'admingroup');
	      $parentGroup = $this->objFromFixture('Group', 'parentgroup');
	      $childGroup = $this->objFromFixture('Group', 'childgroup');

	      // Test single group relation through checkboxsetfield
	      $form = new GroupTest_MemberForm($this, 'Form');
	      $member = $this->objFromFixture('GroupTest_Member', 'admin');
	      $form->loadDataFrom($member);
	      $checkboxSetField = $form->Fields()->fieldByName('Groups');
	      $checkboxSetField->setValue(array(
	         $adminGroup->ID => $adminGroup->ID, // keep existing relation
	         $parentGroup->ID => $parentGroup->ID, // add new relation
	      ));
	      $form->saveInto($member);
	      $updatedGroups = $member->Groups();

	      $this->assertEquals(
			array($adminGroup->ID, $parentGroup->ID),
	         $updatedGroups->column(),
	         "Adding a toplevel group works"
	      );

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
	      $this->assertEquals(
			array($adminGroup->ID),
	         $updatedGroups->column(),
	         "Removing a previously added toplevel group works"
	      );

	      // Test adding child group

	   }
	
	function testCollateAncestorIDs() {
		$parentGroup = $this->objFromFixture('Group', 'parentgroup');
		$childGroup = $this->objFromFixture('Group', 'childgroup');
		$orphanGroup = new Group();
		$orphanGroup->ParentID = 99999;
		$orphanGroup->write();
		
		$this->assertEquals(
			array($parentGroup->ID), 
			$parentGroup->collateAncestorIDs(),
			'Root node only contains itself'
		);
		
		$this->assertEquals(
			array($childGroup->ID, $parentGroup->ID), 
			$childGroup->collateAncestorIDs(),
			'Contains parent nodes, with child node first'
		);
		
		$this->assertEquals(
			array($orphanGroup->ID), 
			$orphanGroup->collateAncestorIDs(),
			'Orphaned nodes dont contain invalid parent IDs'
		);
	}

	public function testDelete() {
		$group = $this->objFromFixture('Group', 'parentgroup');
		$groupID = $group->ID;
		$childGroupID = $this->idFromFixture('Group', 'childgroup');
		$group->delete();

		$this->assertEquals(0, DataObject::get('Group', "\"ID\" = {$groupID}")->Count(), 'Group is removed');
		$this->assertEquals(0, DataObject::get('Permission', "\"GroupID\" = {$groupID}")->Count(), 'Permissions removed along with the group');
		$this->assertEquals(0, DataObject::get('Group', "\"ParentID\" = {$groupID}")->Count(), 'Child groups are removed');
		$this->assertEquals(0, DataObject::get('Group', "\"ParentID\" = {$childGroupID}")->Count(), 'Grandchild groups are removed');
	}

}

class GroupTest_Member extends Member implements TestOnly {
   
   function getCMSFields() {
      $groups = DataObject::get('Group');
      $groupsMap = ($groups) ? $groups->map() : false;
      $fields = new FieldList(
         new HiddenField('ID', 'ID'),
         new CheckboxSetField(
            'Groups',
            'Groups',
            $groupsMap
         )
      );
      
      return $fields;
   }
   
}

class GroupTest_MemberForm extends Form {
   
   function __construct($controller, $name) {
      $fields = singleton('GroupTest_Member')->getCMSFields();
      $actions = new FieldList(
         new FormAction('doSave','save')
      );
      
      parent::__construct($controller, $name, $fields, $actions);
   }
   
   function doSave($data, $form) {
      // done in testing methods
   }
   
}
