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

