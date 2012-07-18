<?php
/**
 * Password input field.
 * @package forms
 * @subpackage fields-formattedinput
 */
class PasswordField extends TextField {

	/**
	 * Returns an input field, class="text" and type="text" with an optional
	 * maxlength
	 */
	function __construct($name, $title = null, $value = "") {
		if(count(func_get_args()) > 3) Deprecation::notice('3.0', 'Use setMaxLength() instead of constructor arguments', Deprecation::SCOPE_GLOBAL);

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

