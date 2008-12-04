<?php

/**
 * @package forms
 * @subpackage fields-formattedinput
 */

/**
 * Field for displaying phone numbers. It separates the number, the area code and optionally the country code
 * and extension.
 * @package forms
 * @subpackage fields-formattedinput
 */
class PhoneNumberField extends FormField {
	
	protected $areaCode;
	protected $countryCode;
	protected $ext;
	
	public function __construct( $name, $title = null, $value = '', $extension = null, 
		$areaCode = null, $countryCode = null, $form = null ) {
		
		$this->areaCode = $areaCode;
		$this->ext = $extension;
		$this->countryCode = $countryCode;
		
		parent::__construct( $name, $title, $value, $form );
	}
	
	public function Field() {
		$field = new FieldGroup( $this->name );
		$field->setID("{$this->name}_Holder");

    
		list( $countryCode, $areaCode, $phoneNumber, $extension ) = $this->parseValue();
		
		$hasTitle = false;

        if ($this->value=="")
        {
            $countryCode=$this->countryCode;
            $areaCode=$this->areaCode;
            $extension=$this->ext;
        }
		
		if( $this->countryCode !== null )
			$field->push( new NumericField( $this->name.'[Country]', '+', $countryCode, 4 ) );
			
		if( $this->areaCode !== null ){
			$field->push( new NumericField( $this->name.'[Area]', '(', $areaCode, 4 ) );
			$field->push( new NumericField( $this->name.'[Number]', ')', $phoneNumber, 10 ) );
		}else{
			$field->push( new NumericField( $this->name.'[Number]', '', $phoneNumber, 10 ) );
		}
		
		if( $this->ext !== null )
			$field->push( new NumericField( $this->name.'[Extension]', 'ext', $extension, 6 ) );
			
		return $field;
	}
	
	public function setValue( $value ) {
		$this->value = self::joinPhoneNumber( $value );
		return $this;
	}
	
	public static function joinPhoneNumber( $value ) {
		if( is_array( $value ) ) {
			$completeNumber = '';
			if( isset($value['Country']) && $value['Country']!=null) {
				$completeNumber .= '+' . $value['Country'];
			}

			if( isset($value['Area']) && $value['Area']!=null) {
				$completeNumber .= '(' . $value['Area'] . ')';
			}
				
			$completeNumber .= $value['Number'];
			
			if( isset($value['Extension']) && $value['Extension']!=null) {
				$completeNumber .= '#' . $value['Extension'];
			}
			
			return $completeNumber;
		} else
			return $value;
	}
	
	protected function parseValue() {
		if( !is_array( $this->value ))        
			preg_match( '/^(?:(?:\+(\d+))?\s*\((\d+)\))?\s*([0-9A-Za-z]*)\s*(?:[#]\s*(\d+))?$/', $this->value, $parts );
		else
			return array( '', '', $this->value, '' );
            
		if(is_array($parts)) array_shift( $parts );

		for ($x=0;$x<=3;$x++) {
			if (!isset($parts[$x])) $parts[]='';
		}
			
		return $parts;
	}
	
	public function saveInto( $record ) {
    
		list( $countryCode, $areaCode, $phoneNumber, $extension ) = $this->parseValue();
		$fieldName = $this->name;
		
		$completeNumber = '';
		
		if( $countryCode )
			$completeNumber .= '+' . $countryCode;
			
		if( $areaCode )
			$completeNumber .= '(' . $areaCode . ')';
			
		$completeNumber .= $phoneNumber;
		
		if( $extension )
			$completeNumber .= '#' . $extension;

		$record->$fieldName = $completeNumber;
	}
	
	/**
	 * @todo Very basic validation at the moment
	 */
	function jsValidation() {
		$formID = $this->form->FormName();
		
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validatePhoneNumber: function(fieldName) {
			if(!$(fieldName + "_Holder")) return true;
			
			// Phonenumbers are split into multiple values, so get the inputs from the form.
			var parts = $(fieldName + "_Holder").getElementsByTagName('input');
			var isNull = true;
			
			// we're not validating empty fields (done by requiredfields)
			for(i=0; i < parts.length ; i++ ) {
				isNull = (parts[i].value == null || parts[i].value == "") ? isNull && true : false;
			}
			
			if(!isNull) {
				// Concatenate the string values from the parts of the input.
				var joinedNumber = ""; 
				for(i=0; i < parts.length; i++) joinedNumber += parts[i].value;
				if(!joinedNumber.match(/^[0-9\+\-\(\)\s\#]*\$/)) {
					// TODO Find a way to mark multiple error fields
					validationError(
						fieldName+"-Number",
						"Please enter a valid phone number",
						"validation",
						false
					);
				}
			}
			return true;			
		}
	}
});
JS;
		Requirements :: customScript($jsFunc, 'func_validatePhoneNumber');
		
		return "\$('$formID').validatePhoneNumber('$this->name');";
	}
	
	/**
	 * @todo Very basic validation at the moment
	 */
	function validate($validator){
		$valid = preg_match(
			'/^[0-9\+\-\(\)\s\#]*$/',
			$this->joinPhoneNumber($this->value)
		);
		
		if(!$valid){
			$validator->validationError(
				$this->name, 
				_t('PhoneNumberField.VALIDATION', "Please enter a valid phone number"),
				"validation", 
				false
			);
			return false;
		}
		
		return true;
	}
}
?>