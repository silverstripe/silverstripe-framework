<?php
/**
 * Password input field.
 * @package forms
 * @subpackage fields-formattedinput
 */
class PasswordField extends TextField {

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


	function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('type' => 'password')
		);
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

	function Type() {
		return 'text password';
	}
}

?>