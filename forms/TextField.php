<?php

/**
 * @package forms
 * @subpackage fields-basic
 */

/**
 * Text input field.
 * @package forms
 * @subpackage fields-basic
 */
class TextField extends FormField {
	protected $maxLength;
	
	/**
	 * Returns an input field, class="text" and type="text" with an optional maxlength
	 */
	function __construct($name, $title = null, $value = "", $maxLength = null, $form = null){
		$this->maxLength = $maxLength;
		parent::__construct($name, $title, $value, $form);
	}
	
	function Field() {
		$extraClass = $this->extraClass();
		
		$fieldSize = $this->maxLength ? min( $this->maxLength, 30 ) : 30;
		
		if($this->maxLength) {
			return "<input class=\"text maxlength$extraClass\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" maxlength=\"$this->maxLength\" size=\"$fieldSize\" />";
		} else {
			return "<input class=\"text$extraClass\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />"; 
		}
	}
	
	function InternallyLabelledField() {
		if(!$this->value) $this->value = $this->Title();
		return $this->Field();
	}
}
?>
