<?php
/**
 * Text field that automatically checks that the value entered is unique for the given
 * set of fields in a given set of tables
 * @deprecated 2.5
 * @package forms
 * @subpackage fields-formattedinput
 */
class UniqueRestrictedTextField extends UniqueTextField {
	
	protected $charRegex;
	protected $charReplacement;
	protected $charMessage;
	
	function __construct($name, $restrictedField, $restrictedTable, $restrictedMessage, $charRegex, $charReplacement, $charMessage, $title = null, $value = "", $maxLength = null ){
		$this->charRegex = $charRegex;
		$this->charReplacement = $charReplacement;
		$this->charMessage = $charMessage;
		
		parent::__construct($name, $restrictedField, $restrictedTable, $restrictedMessage, $title, $value, $maxLength);	
	}
	 
	function Field() {
			return parent::Field()."<input type=\"hidden\" name=\"restricted-chars[".$this->id()."]\" id=\"".$this->id()."-restricted-chars\" value=\"".$this->charRegex."\" /><input type=\"hidden\" name=\"restricted-chars[".$this->id()."]\" id=\"".$this->id()."-restricted-chars-replace\" value=\"".$this->charReplacement."\" /><input type=\"hidden\" name=\"restricted-chars[".$this->id()."]\" id=\"".$this->id()."-restricted-chars-message\" value=\"".$this->charMessage."\" />";
	}
}
?>