<?php
/**
 * Password input field.
 * @package forms
 * @subpackage fields-formattedinput
 */
class PasswordField extends TextField {

	/**
	 * Controls the autocomplete attribute on the field.
	 *
	 * Setting it to false will set the attribute to "off", which will hint the browser
	 * to not cache the password and to not use any password managers.
	 */
	private static $autocomplete;

	/**
	 * Returns an input field, class="text" and type="text" with an optional
	 * maxlength
	 */
	public function __construct($name, $title = null, $value = "") {
		if(count(func_get_args()) > 3) {
			Deprecation::notice('3.0', 'Use setMaxLength() instead of constructor arguments',
				Deprecation::SCOPE_GLOBAL);
		}

		parent::__construct($name, $title, $value);
	}


	public function getAttributes() {
		$attributes = array_merge(
			parent::getAttributes(),
			array('type' => 'password')
		);

		$autocomplete = Config::inst()->get('PasswordField', 'autocomplete');
		if (isset($autocomplete)) {
			$attributes['autocomplete'] = $autocomplete ? 'on' : 'off';
		}

		return $attributes;
	}

	/**
	 * Makes a pretty readonly field with some stars in it
	 */
	public function performReadonlyTransformation() {
		$field = $this->castedCopy('ReadonlyField');
		$field->setValue('*****');
		
		return $field;
	}

	public function Type() {
		return 'text password';
	}
}

