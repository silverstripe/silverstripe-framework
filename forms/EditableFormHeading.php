<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * Allows an editor to insert a generic heading into a field
 * @package forms
 * @subpackage fieldeditor
 */
class EditableFormHeading extends EditableFormField {
	static $singular_name = 'Form heading';
	static $plural_name = 'Form headings';
	
	function getFormField() {
		// TODO customise this
		return new LabelField( $this->Title, 'FormHeading' );
		// return '<h3>' . $this->Title . '</h3>';
	}
	
	function showInReports() {
		return false;
	}
}
?>