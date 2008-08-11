<?php
/**
 * @package sapphire
 * @subpackage testing
 */
class GroupTest extends FunctionalTest {
   
   static $fixture_file = 'sapphire/tests/GroupTest.yml';
   
   function testMemberGroupRelationForm() {
      $adminGroup = $this->fixture->objFromFixture('Group', 'admingroup');
      $parentGroup = $this->fixture->objFromFixture('Group', 'parentgroup');
      $childGroup = $this->fixture->objFromFixture('Group', 'childgroup');
      
      // Test single group relation through checkboxsetfield
      $form = new GroupTest_MemberForm($this, 'Form');
      $member = $this->fixture->objFromFixture('GroupTest_Member', 'admin');
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