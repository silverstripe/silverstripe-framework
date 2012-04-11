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
	
	public function __construct( $name, $title = null, $value = '', $extension = null, $areaCode = null, $countryCode = null) {
		
		$this->areaCode = $areaCode;
		$this->ext = $extension;
		$this->countryCode = $countryCode;
		
		parent::__construct($name, $title, $value);
	}
	
	public function Field($properties = array()) {
		$fields = new FieldGroup( $this->name );
		$fields->setID("{$this->name}_Holder");
		list($countryCode, $areaCode, $phoneNumber, $extension) = $this->parseValue();
		$hasTitle = false;

    if ($this->value=="") {
      $countryCode=$this->countryCode;
      $areaCode=$this->areaCode;
      $extension=$this->ext;
    }
		
		if($this->countryCode !== null) {
			$fields->push(new NumericField($this->name.'[Country]', '+', $countryCode, 4));
		}
			
		if($this->areaCode !== null) {
			$fields->push(new NumericField($this->name.'[Area]', '(', $areaCode, 4));
			$fields->push(new NumericField($this->name.'[Number]', ')', $phoneNumber, 10));
		} else {
			$fields->push(new NumericField($this->name.'[Number]', '', $phoneNumber, 10));
		}
		
		if($this->ext !== null) {
			$field->push(new NumericField( $this->name.'[Extension]', 'ext', $extension, 6));
		}

		foreach($fields as $field) {
			$field->setDisabled($this->isDisabled());
			$field->setReadonly($this->isReadonly());
		}
			
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
	
	public function saveInto(DataObjectInterface $record) {
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
