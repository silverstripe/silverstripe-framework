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
class EditableRadioOption extends DataObject {
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
		"Value" => "Varchar",
		"Sort" => "Int"
	);
	static $has_one = array(
		"Parent" => "EditableRadioField",
	);

	static $singular_name = 'Radio option';
	static $plural_name = 'Radio options';

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
	
	function DefaultSelect() {
		$disabled = ($this->readonly) ? " disabled=\"disabled\"" : '';
			
		if($this->Parent()->getField('Default') == $this->ID) {
			$default = " checked=\"checked\"";
		} else {
			$default = '';
		}
		
		return "<input class=\"radio\" type=\"radio\" name=\"Fields[{$this->ParentID}][Default]\" value=\"{$this->ID}\"".$disabled.$default." />";
	}
}
?>