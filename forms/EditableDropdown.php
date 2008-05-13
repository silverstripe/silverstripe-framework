<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableDropdown
 * Represents a modifiable dropdown box on a form
 * @package forms
 * @subpackage fieldeditor
 */
class EditableDropdown extends EditableFormField {
	
	static $has_many = array(
		"Options" => "EditableDropdownOption"
	);
	
	static $singular_name = 'Dropdown';
	static $plural_name = 'Dropdowns';
	
	function delete() {
  		$options = $this->Options();

		foreach( $options as $option )
			$option->delete();
		
		parent::delete();   
	}
	
	function EditSegment() {
		return $this->renderWith( $this->class );
	}
	
	function populateFromPostData( $data ) {
		
		parent::populateFromPostData( $data );
		
		$fieldSet = $this->Options();
		
		$deletedOptions = explode( ',', $data['Deleted'] );
	
		// store default, etc
		foreach( $fieldSet as $option ) {
			
			if( $deletedOptions && array_search( $option->ID, $deletedOptions ) !== false ) {
				$option->delete();
				continue;
			}
			
			if(isset($data[$option->ID])) {
				$option->setField( 'Default', isset($data['Default']) ? ($option->ID == $data['Default']) : false );
				$option->populateFromPostData( $data[$option->ID] );
			} 
				
			unset( $data[$option->ID] );
		}
		
		$optionNumber = 0;
		foreach( $data as $tempID => $optionData ) {
			
			if( !$tempID || !is_array( $optionData ) || empty( $optionData ) || !preg_match('/^_?\d+$/', $tempID ) )
				continue;
			
			// what will we name the new option?
			$newOption = new EditableDropdownOption();
			$newOption->Name =  'option' . (string)$optionNumber++;
			$newOption->ParentID = $this->ID;
			if(isset($data['Default'])) {
				$newOption->setField('Default', $tempID == $data['Default']);
			}
			
			if( Director::is_ajax() ) {
				$fieldID = $this->ID;
				$fieldEditorName = $this->editor ? $this->editor->Name() : 'Fields';
				$prefix = $fieldEditorName . '[' . $fieldID . ']';
				$newID = $newOption->ID;
				$newSort = $newOption->Sort;
				echo "\$('". $fieldEditorName . "[$fieldID]').updateOption('$prefix','$tempID','$newID','$newSort');";
			}
			
			if( !$optionData['Sort'] ) {
				
			}
			
			$newOption->populateFromPostData( $optionData );
		}
	}
	
	function getFormField() {
		return $this->createField();
	}
	
	function getFilterField() {
		return $this->createField( true );
	}
	
	function createField( $asFilter = false ) {
		$optionSet = $this->Options();
		$options = array();
		
		if( $asFilter )
			$options['-1'] = "(Any)";
		
		$defaultOption = '-1';
		
		foreach( $optionSet as $option ) {
			$options[$option->Title] = $option->Title;
			if( $option->getField('Default') && !$asFilter ) $defaultOption = $option->Title;
		}
		
		return new DropdownField( $this->Name, $this->Title, $options, $defaultOption );	
	}
	
	function TemplateOption() {
		$option = new EditableDropdownOption();
		return $option->EditSegment();
	}
	
	function duplicate() {
		$clonedNode = parent::duplicate();
		
		foreach( $this->Options() as $field ) {
			$newField = $field->duplicate();
			$newField->ParentID = $clonedNode->ID;
			$newField->write();
		}
		
		return $clonedNode;
	}
}
?>