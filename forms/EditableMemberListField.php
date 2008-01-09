<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * Creates an editable field that displays members in a given group
 * @package forms
 * @subpackage fieldeditor
 */
class EditableMemberListField extends EditableFormField {
	
	static $has_one = array(
		'Group' => 'Group'
	);
	
	static $singular_name = 'Member list field';
	static $plural_name = 'Member list fields';
	
	public function DefaultField() {
		// return new TreeDropdownField( "Fields[{$this->ID}][GroupID]", 'Group' );
		
		$groups = DataObject::get('Group');
		
		foreach( $groups as $group )
			$groupArray[$group->ID] = $group->Title;
		
		return new DropdownField( "Fields[{$this->ID}][GroupID]", 'Group', $groupArray, $this->GroupID );
	}
	
	public function populateFromPostData( $data ) {
		$this->GroupID = $data['GroupID'];
		
		parent::populateFromPostData( $data );
	}
	
	function getFormField() {
		return new DropdownField( $this->Name, $this->Title, Member::mapInGroups( $this->GroupID ) );
	}
	
	function getValueFromData( $data ) {
		$value = $data[$this->Name];
		
		$member = DataObject::get_one('Member', "Member.ID = {$value}");
		return $member->getName();
	}
}
?>