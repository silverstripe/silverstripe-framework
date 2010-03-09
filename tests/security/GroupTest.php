<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class GroupTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/security/GroupTest.yml';
	
	/**
	 * Test the Group::map() function
	 */
	function testGroupMap() {
		/* Group::map() returns an SQLMap object implementing iterator.  You can use foreach to get ID-Title pairs. */
		
		// We will iterate over the map and build mapOuput to more easily call assertions on the result.
		$map = Group::map();
		foreach($map as $k => $v) {
			$mapOutput[$k] = $v;
		}
		
		$group1 = $this->objFromFixture('Group', 'group1');
		$group2 = $this->objFromFixture('Group', 'group2');
		
		/* We have added 2 groups to our fixture.  They should both appear in $mapOutput. */
		$this->assertEquals($mapOutput[$group1->ID], $group1->Title);
		$this->assertEquals($mapOutput[$group2->ID], $group2->Title);
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

	      $controlGroups = new Member_GroupSet(
	         $adminGroup,
	         $parentGroup
	      );
	      $this->assertEquals(
	         $updatedGroups->Map('ID','ID'),
	         $controlGroups->Map('ID','ID'),
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
	      $controlGroups = new Member_GroupSet(
	         $adminGroup
	      );
	      $this->assertEquals(
	         $updatedGroups->Map('ID','ID'),
	         $controlGroups->Map('ID','ID'),
	         "Removing a previously added toplevel group works"
	      );

	      // Test adding child group

	   }
	
	function testDelete() {
		$adminGroup = $this->objFromFixture('Group', 'admingroup');
		
		$adminGroup->delete();
		
		$this->assertNull(DataObject::get('Group', "\"ID\"={$adminGroup->ID}"), 'Group is removed');
		$this->assertNull(DataObject::get('Permission',"\"GroupID\"={$adminGroup->ID}"), 'Permissions removed along with the group');
	}
}

class GroupTest_Member extends Member implements TestOnly {
   
   function getCMSFields() {
      $groups = DataObject::get('Group');
      $groupsMap = ($groups) ? $groups->toDropDownMap() : false;
      $fields = new FieldSet(
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
      $actions = new FieldSet(
         new FormAction('doSave','save')
      );
      
      parent::__construct($controller, $name, $fields, $actions);
   }
   
   function doSave($data, $form) {
      // done in testing methods
   }
   
}
?>