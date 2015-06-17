<?php

/**
 * Password input field.
 *
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
	 * Returns an input field.
	 *
	 * @param string $name
	 * @param null|string $title
	 * @param string $value
	 */
	public function __construct($name, $title = null, $value = '') {
		if(count(func_get_args()) > 3) {
			Deprecation::notice(
				'3.0', 'Use setMaxLength() instead of constructor arguments',
				Deprecation::SCOPE_GLOBAL
			);
		}

		parent::__construct($name, $title, $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		$attributes = array(
			'type' => 'password',
		);

		$autocomplete = Config::inst()->get('PasswordField', 'autocomplete');

		if($autocomplete) {
			$attributes['autocomplete'] = 'on';
		} else {
			$attributes['autocomplete'] = 'off';
		}

		return array_merge(
			parent::getAttributes(),
			$attributes
		);
	}

	/**
	 * Creates a read-only version of the field.
	 *
	 * @return FormField
	 */
	public function performReadonlyTransformation() {
		$field = $this->castedCopy('ReadonlyField');

		$field->setValue('*****');

		return $field;
	}

	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'text password';
	}
}
