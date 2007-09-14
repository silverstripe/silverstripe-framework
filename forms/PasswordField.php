<?php
/**
 * Text input field.
 */
class PasswordField extends FormField {
	protected $maxLength;
	
	/**
	 * Returns an input field, class="text" and type="text" with an optional maxlength
	 */
	function __construct($name, $title = null, $value = "", $maxLength = null ){
		$this->maxLength = $maxLength;
		parent::__construct($name, $title, $value);	
	}
	 
	function Field() {
		if($this->maxLength){
			return "<input class=\"text\" type=\"password\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" maxlength=\"$this->maxLength\" size=\"$this->maxLength\"/>";
		}else{
			return "<input class=\"text\" type=\"password\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />"; 
		}
	}
	
	/**
	 * Makes a pretty readonly field with stars the length of the password instead of the 
	 * actual one. ;)
	 */
	function performReadonlyTransformation() {
		$stars = '';
		$count = strlen($this->attrValue());
		do{	$stars .= "*";} while(strlen($stars) <= $count);
		 
		$field = new ReadonlyField($this->name,$this->title ? $this->title : "",$stars);
		$field->setForm($this->form);
		return $field;
	}
}
?>