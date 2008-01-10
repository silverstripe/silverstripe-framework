<?php

/**
 * @package forms
 * @subpackage fieldeditor
 */

/**
 * EditableTextField
 * This control represents a user-defined field in a user defined form
 * @package forms
 * @subpackage fieldeditor
 */
class EditableTextField extends EditableFormField {
	
	static $db = array(
		"Size" => "Int",
		"MinLength" => "Int",
		"MaxLength" => "Int",
		"Rows" => "Int"
	);
	
	static $singular_name = 'Text field';
	static $plural_name = 'Text fields';
	
	function __construct( $record = null, $isSingleton = false ) {
		$this->Size = 32;
		$this->MinLength = 1;
		$this->MaxLength = 32;
		$this->Rows = 1;
		parent::__construct( $record, $isSingleton );
	}	
	
	function ExtraOptions() {
		
		// eventually replace hard-coded "Fields"?
		$baseName = "Fields[$this->ID]";
		
		$extraFields = new FieldSet(
			new TextField($baseName . "[Size]", _t('EditableTextField.TEXTBOXLENGTH', 'Length of text box'), (string)$this->Size),
			new FieldGroup(_t('EditableTextField.TEXTLENGTH', 'Text length'),
				new TextField($baseName . "[MinLength]", "", (string)$this->MinLength),
				new TextField($baseName . "[MaxLength]", " - ", (string)$this->MaxLength)
			),
			new TextField($baseName . "[Rows]", _t('EditableTextField.NUMBERROWS', 'Number of rows'), (string)$this->Rows)
		);
		
		foreach( parent::ExtraOptions() as $extraField )
			$extraFields->push( $extraField );
			
		if( $this->readonly )
			$extraFields = $extraFields->makeReadonly();	
			
		return $extraFields;		
	}
	
	function populateFromPostData( $data ) {

		$this->Size = !empty( $data['Size'] ) ? $data['Size'] : 32;
		$this->MinLength = !empty( $data['MinLength'] ) ? $data['MinLength'] : 1;
		$this->MaxLength = !empty( $data['MaxLength'] ) ? $data['MaxLength'] : 32;
		$this->Rows = !empty( $data['Rows'] ) ? $data['Rows'] : 1;
		parent::populateFromPostData( $data );
	}
	
	function getFormField() {
		return $this->createField();
	}
	
	function getFilterField() {
		return $this->createField( true );
	}
	
	function createField( $asFilter = false ) {
		if( $this->Rows == 1 )
			return new TextField( $this->Name, $this->Title, ( $asFilter ) ? "" : $this->getField('Default'), ( $this->Size && $this->Size > 0 ) ? $this->Size : null );
		else
			return new TextareaField( $this->Name, $this->Title, $this->Rows, $this->Size, ( $asFilter ) ? "" : $this->getField('Default') );
	}
	
	/**
	 * Populates the default fields. 
	 */
	function DefaultField() {
		$disabled = '';
		if( $this->readonly ){
			$disabled = " disabled=\"disabled\"";
		} else {
			$disabled = '';
		}
		if( $this->Rows == 1 ){
		        return '<div class="field text"><label class="left">'._t('EditableTextField.DEFAULTTEXT', 'Default Text').' </label> <input class="defaultText" name="Fields['.Convert::raw2att( $this->ID ).'][Default]" type="text" value="'.Convert::raw2att( $this->getField('Default') ).'"'.$disabled.' /></div>';
		}else{
			return '<div class="field text"><label class="left">'._t('EditableTextField.DEFAULTTEXT', 'Default Text').' </label> <textarea class="defaultText" name="Fields['.Convert::raw2att( $this->ID ).'][Default]"'.$disabled.'>'.Convert::raw2att( $this->getField('Default') ).'</textarea></div>';
		}
	}
}
?>