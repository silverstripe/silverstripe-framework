<?php
	/**
	 * EditableCheckbox
	 * A user modifiable checkbox on a UserDefinedForm
	 */
	class EditableCheckbox extends EditableFormField {
		
		// Could remove this and just use value
		static $db = array(
			"Checked" => "Boolean"
		);
		
		static $singular_name = 'Checkbox';
		static $plural_name = 'Checkboxes';
		
		function CheckboxField() {
			$checkbox = new CheckboxField("Fields[".$this->ID."][Default]", "Checked by default", $this->getField('Default'));
			
			if( $this->readonly )
				$checkbox = $checkbox->performReadonlyTransformation();
			
			return $checkbox->FieldHolder();
		}
		
		function populateFromPostData( $data ) {
			$this->setField('Checked', isset($data['Checked']) ? $data['Checked'] : null);
			parent::populateFromPostData( $data );
		}
		
		function getFormField() {
			return new CheckboxField( $this->Name, $this->Title, $this->getField('Default') );
		}
		
		function getFilterField() {
			return new OptionsetField( $this->Name, 
																 $this->Title, 
																 array( '-1' => '(Any)',
																 				'on' => 'Selected',
																 				'0' => 'Not selected' )
			);
		}
	}
?>