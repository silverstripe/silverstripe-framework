<?php
/**
 * A Text field that cannot contain certain characters
 * @deprecated 2.5
 * @package forms
 * @subpackage fields-formatted
 */
class RestrictedTextField extends TextField {

	protected $restrictedChars;

	function __construct($name, $title = null, $value = "", $restrictedChars = "", $maxLength = null){
		$this->restrictedChars = $restrictedChars;
		parent::__construct($name, $title, $value);	
	}
	
	function Field() {
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript( SAPPHIRE_DIR . '/javascript/UniqueFields.js' );
		
		if($this->maxLength){
			$field = "<input class=\"text restricted\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" maxlength=\"$this->maxLength\" />";
		}else{
			$field = "<input class=\"text restricted\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />"; 
		}
		
		return $field."<input type=\"hidden\" name=\"restricted-chars[".$this->id()."]\" id=\"".$this->id()."-restricted-chars\" value=\"".$this->restrictedChars."\" />";
	}
}
?>