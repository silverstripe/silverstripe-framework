<?php
/**
 * Password input field.
 * @package forms
 * @subpackage fields-formattedinput
 */
class PasswordField extends FormField {

	/**
	 * maxlength of the password field
	 *
	 * @var int
	 */
	protected $maxLength;


	/**
	 * Returns an input field, class="text" and type="text" with an optional
	 * maxlength
	 */
	function __construct($name, $title = null, $value = "", $maxLength = null) {
		$this->maxLength = $maxLength;
		parent::__construct($name, $title, $value);
	}


	function Field() {
		$disabled = $this->isDisabled()?"disabled=\"disabled\"":""; 
		$readonly = $this->isReadonly()?"readonly=\"readonly\"":"";
		if($this->maxLength) {
			return "<input class=\"text\" type=\"password\" id=\"" . $this->id() .
				"\" name=\"{$this->name}\" value=\"" . $this->attrValue() .
				"\" maxlength=\"$this->maxLength\" size=\"$this->maxLength\" $disabled $readonly />"; 
		} else {
			return "<input class=\"text\" type=\"password\" id=\"" . $this->id() .
				"\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" $disabled $readonly />"; 
		}
	}


	/**
	 * Makes a pretty readonly field with some stars in it
	 */
	function performReadonlyTransformation() {
		$stars = '*****';

		$field = new ReadonlyField($this->name, $this->title ? $this->title : '', $stars);
		$field->setForm($this->form);
		$field->setReadonly(true);
		return $field;
	}
}

?>