<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableDropdownOption
 * Represents a single entry in an EditableRadioField
 * @package forms
 * @subpackage fieldeditor
 */
class EditableCheckboxOption extends DataObject {
	static $default_sort = "Sort";

	// add required here?
	static $db = array(
		"Name" => "Varchar",
		"Title" => "Varchar",
		"Default" => "Boolean",
		"Sort" => "Int"
	);
	static $has_one = array(
		"Parent" => "EditableCheckboxGroupField",
	);

	static $singular_name = "Checkbox option";
	static $plural_name = "Checkbox options";

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
		$this->setField('Default', $data['Default']);
		$this->Sort = $data['Sort'];
		$this->write();
	}

	function Option() {
		// return new radio field
		/*$title = Convert::raw2att( $this->Title );
	
		$default = "";
	
		if( $this->getField('Default') )
			$default = '+';
		else
			$default = '-';
	 
		//Debug::show($this);
		return '<input type="text" name="Fields['.$this->ParentID.']['.$this->ID.'][Title]" value="'.$default.$title.'" />';*/
	
		return $this->EditSegment();
	}

	function ReadonlyOption() {
		$this->readonly = true;
		return $this->EditSegment();
	}

	function DefaultSelect() {
		if( $this->readonly )
			$disabled = " disabled=\"disabled\"";
		
		if( $this->getField('Default') )
			$default = " checked=\"checked\"";
	
		return "<input class=\"checkbox\" type=\"checkbox\" name=\"Fields[{$this->ParentID}][{$this->ID}][Default]\" value=\"1\"".$disabled.$default." />";
	}
}
?>