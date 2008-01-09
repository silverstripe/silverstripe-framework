<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableDropdown
 * Represents a set of selectable radio buttons
 * @package forms
 * @subpackage fieldeditor
 */
class EditableCheckboxGroupField extends EditableFormField {
	
	protected $readonly;

	function ReadonlyOption() {
		$this->readonly = true;
		return $this->Option();
	}

	function isReadonly() {
		return $this->readonly;
	}
	
	static $has_many = array(
		"Options" => "EditableCheckboxOption"
	);
	
	static $singular_name = "Checkbox group";
	static $plural_name = "Checkbox groups";
	
	function duplicate() {
		$clonedNode = parent::duplicate();
		
		foreach( $this->Options() as $field ) {
			$newField = $field->duplicate();
			$newField->ParentID = $clonedNode->ID;
			$newField->write();
		}
		
		return $clonedNode;
	}
	
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
			
			if( $data[$option->ID] )
				$option->populateFromPostData( $data[$option->ID] );
				
			unset( $data[$option->ID] );
		}
		
		foreach( $data as $tempID => $optionData ) {
			
			if( !$tempID || !is_array( $optionData ) || empty( $optionData ) || !preg_match('/^_?\d+$/', $tempID ) )
				continue;
			
			// what will we name the new option?
			$newOption = new EditableCheckboxOption();
			$newOption->Name =  'option' . (string)$optionNumber++;
			$newOption->ParentID = $this->ID;
			$newOption->populateFromPostData( $optionData );
		}
	}
	
	function DefaultOption() {
		$defaultOption = 0;
		
		foreach( $this->Options() as $option ) {
			if( $option->getField('Default') )
				return $defaultOption;
			else
				$defaultOption++;
		}
		
		return -1;
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
			$options['-1'] = '(Any)';
		
		$defaultOption = '-1';
		
		/*foreach( $optionSet as $option ) {
			$options[$option->Title] = $option->Title;
		}*/
		
		// return radiofields
		$checkboxSet = new CheckboxSetField( $this->Name, $this->Title, $optionSet, $optionSet );
			
		return $checkboxSet;
	}
	
	function getValueFromData( $data ) {
		if( empty( $data[$this->Name] ) ) {
			return "";
		}
		
		$entries = $data[$this->Name];
		
		if( !is_array( $data[$this->Name] ) ) {
			$entries = array( $data[$this->Name] );
		}
			
		$selectedOptions = DataObject::get( 'EditableCheckboxOption', "ParentID={$this->ID} AND ID IN (".implode(',',$entries).")" );
		foreach( $selectedOptions as $selected ) {
			if( !$result )
				$result = $selected->ID;
			else
				$result .= "," . $selected->ID;
		}
		return $result;
	}
		
		function TemplateOption() {
			$option = new EditableCheckboxOption();
			return $option->EditSegment();
		}
	}
?>