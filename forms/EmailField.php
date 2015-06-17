<?php

/**
 * Text input field with validation for correct email format according to RFC 2822.
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class EmailField extends TextField {
	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'email text';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'type' => 'email',
			)
		);
	}

	/**
	 * Validates for RFC 2822 compliant email addresses.
	 *
	 * @see http://www.regular-expressions.info/email.html
	 * @see http://www.ietf.org/rfc/rfc2822.txt
	 *
	 * @param Validator $validator
	 *
	 * @return string
	 */
	public function validate($validator) {
		$this->value = trim($this->value);

		$pattern = '^[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$';

		// Escape delimiter characters.
		$safePattern = str_replace('/', '\\/', $pattern);

		if($this->value && !preg_match('/' . $safePattern . '/i', $this->value)) {
			$validator->validationError(
				$this->name,
				_t('EmailField.VALIDATION', 'Please enter an email address'),
				'validation'
			);

			return false;
		}

		return true;
	}
}
