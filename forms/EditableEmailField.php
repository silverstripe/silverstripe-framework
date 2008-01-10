<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableEmailField
 * Allow users to define a validating editable email field for a UserDefinedForm
 * @package forms
 * @subpackage fieldeditor
 */
class EditableEmailField extends EditableFormField {
	
	static $db = array(
		"SendCopy" => "Boolean"
	);
	
	static $singular_name = 'Email field';
	static $plural_name = 'Email fields';
	
	function populateFromPostData( $data ) {
		$this->SendCopy = !empty( $data['SendCopy'] ) ? "1" : "0";
		
		parent::populateFromPostData( $data );
	}
	
	function ExtraOptions() {
		$baseName = "Fields[$this->ID]";
		
		$extraFields = new FieldSet(
			new CheckboxField( $baseName . "[SendCopy]", _t('EditableEmailField.SENDCOPY', 'Send copy of submission to this address'), $this->SendCopy )
		);
		
		foreach( parent::ExtraOptions() as $extraField )
			$extraFields->push( $extraField );
		
		if( $this->readonly )
			$extraFields = $extraFields->makeReadonly();		
			
		return $extraFields;
	}
	
	function getFormField() {
		return new EmailField( $this->Name, $this->Title, $this->getField('Default') );
	}
	
	function getFilterField() {
		return $this->createField( true );
	}
	
	function DefaultField() {
		$disabled = ($this->readonly) ? " disabled=\"disabled\"" : '';
		
		return '<input class="defaultText" name="Fields['.Convert::raw2att( $this->ID ).'][Default]" type="text" value="'.Convert::raw2att( $this->getField('Default') ).'"'.$disabled.' />';
	}
}
?>