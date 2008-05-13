<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableDropdownOption
 * Represents a single entry in an EditableDropdown
 * @package forms
 * @subpackage fieldeditor
 */  
class EditableDropdownOption extends DataObject {
	protected $readonly;

	function ReadonlyOption() {
		$this->readonly = true;
		return $this->EditSegment();
	}

	function isReadonly() {
		return $this->readonly;
	}

	static $default_sort = "Sort";

	// add required here?
	static $db = array(
		"Name" => "Varchar",
		"Title" => "Varchar",
		"Default" => "Boolean",
		"Sort" => "Int"
	);
	static $has_one = array(
		"Parent" => "EditableDropdown",
	);

	static $singular_name = 'Dropdown option';
	static $plural_name = 'Dropdown options';

	function EditSegment() {
		return $this->renderWith('EditableFormFieldOption');
	}

	function TitleField() {
		return new TextField( "Fields[{$this->ParentID}][{$this->ID}][Title]", null, $this->Title );
	}

	function Name() {
		return "Fields[{$this->ParentID}][{$this->ID}]";
	}

	function populateFromPostData( $data ) {
		$this->Title = $data['Title'];
		$this->Sort = $data['Sort'];
		$this->write();
	}
 	
	function Option() {
		// return new radio field
		/*$title = $this->Title;
	
		$default = "";
	
		if( $this->getField('Default') )
			$default = 'class="default"';
	 
		//Debug::show($this);
		return '<input type="text" name="Fields['.$this->ParentID.']['.$this->ID.'][Title]" value="'.$title.'" '.$default.' />';*/
	
		return $this->EditSegment();
	}

	function DefaultSelect() {
		$disabled = ($this->readonly) ? " disabled=\"disabled\"" : '';		
		
		$default = ($this->Parent()->getField('Default') == $this->ID) ? " checked=\"checked\"" : "";
	
		return "<input class=\"radio\" type=\"radio\" name=\"Fields[{$this->ParentID}][Default]\" value=\"{$this->ID}\"".$disabled.$default." />";
	}
}
?>