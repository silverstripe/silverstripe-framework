<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableDateField
 * Allows a user to add a date field to the Field Editor
 * @package forms
 * @subpackage fieldeditor
 */
class EditableDateField extends EditableFormField {
	static $singular_name = 'Date field';
	static $plural_name = 'Date fields';
	
	function DefaultField() {
		$dmyField = new CalendarDateField( "Fields[{$this->ID}][Default]", "", $this->getField('Default') );
	
		if( $this->readonly )
			$dmyField = $dmyField->performReadonlyTransformation();
			
		return $dmyField;
	}
	
	function populateFromPostData( $data ) {
		/*if( !empty( $data['Default'] ) && !preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['Default'] ) ) {
			if( empty( $data['Year'] ) || !is_numeric( $data['Year'] ) ) $data['Year'] = '2001';
			if( empty( $data['Month'] ) || !is_numeric( $data['Month'] ) ) $data['Month'] = '01';
			if( empty( $data['Day'] ) || !is_numeric( $data['Day'] ) ) $data['Day'] = '01';
			
			// unset( $data['Default'] );
			$data['Default'] = $data['Year'] . '-' . $data['Month'] . '-' . $data['Day'];
		}*/
		
		/*echo "ERROR:";
		Debug::show( $data );
		die();*/
		
		$fieldPrefix = 'Default-';
		
		if( empty( $data['Default'] ) && !empty( $data[$fieldPrefix.'Year'] ) && !empty( $data[$fieldPrefix.'Month'] ) && !empty( $data[$fieldPrefix.'Day'] ) )
			$data['Default'] = $data['Year'] . '-' . $data['Month'] . '-' . $data['Day'];
			
		// Debug::show( $data );
	
		parent::populateFromPostData( $data );
	}
	
	function getFormField() {
		return new CalendarDateField( $this->Name, $this->Title, $this->getField('Default') );
	}
}
?>