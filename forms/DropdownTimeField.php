<?php
/**
 * Field for entering time that provides clock for selecting time.
 * @package forms
 * @subpackage fields-datetime
 */
class DropdownTimeField extends TimeField {
	
	function __construct( $name, $title = null, $value = "", $timeformat = 'H:i a' ){
		parent::__construct( $name, $title, $value, $timeformat );
	}
	
	static function Requirements() {
		Requirements::javascript( SAPPHIRE_DIR . '/javascript/DropdownTimeField.js' );
		Requirements::css( SAPPHIRE_DIR . '/css/DropdownTimeField.css' );
	}
	
	static function HTMLField( $id, $name, $val ) {
		return <<<HTML
			<input type="text" id="$id" name="$name" value="$val"/>
			<img src="sapphire/images/clock-icon.gif" id="$id-icon"/>
			<div class="dropdownpopup" id="$id-dropdowntime"></div>
HTML;
	}
	
	function Field() {
		
		self::Requirements();
		
		$field = parent::Field();

		$id = $this->id();
		$val = $this->attrValue();
		
		$innerHTML = self::HTMLField( $id, $this->name, $val );
			
		return <<<HTML
			<div class="dropdowntime">
				$innerHTML
			</div>
HTML;
	}
}