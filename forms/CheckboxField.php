<?php

/**
 * Single checkbox field.
 *
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxField extends FormField {
	/**
	 * @param int|bool $value
	 *
	 * @return $this
	 */
	public function setValue($value) {
		if($value) {
			$this->value = 1;
		} else {
			$this->value = 0;
		}

		return $this;
	}

	/**
	 * @return null|int
	 */
	public function dataValue() {
		if($this->value) {
			return 1;
		}

		return null;
	}

	/**
	 * @return int
	 */
	public function Value() {
		if($this->value) {
			return 1;
		}

		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		$attributes = array(
			'type' => 'checkbox',
			'value' => 1,
		);

		$attributes['checked'] = null;

		if($this->Value()) {
			$attributes['checked'] = 'checked';
		}

		return array_merge(
			parent::getAttributes(),
			$attributes
		);
	}

	/**
	 * Creates a read-only version of the field.
	 *
	 * @return CheckboxField_Readonly
	 */
	public function performReadonlyTransformation() {
		$field = new CheckboxField_Readonly(
			$this->name,
			$this->title,
			$this->value
		);

		$field->setForm($this->form);

		return $field;
	}
}

/**
 * Readonly version of a checkbox field ('Yes' or 'No').
 *
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxField_Readonly extends ReadonlyField {
	/**
	 * @return static
	 */
	public function performReadonlyTransformation() {
		return clone $this;
	}

	/**
	 * @return string
	 */
	public function Value() {
		if($this->value) {
			return _t('CheckboxField.YESANSWER', 'Yes');
		}

		return _t('CheckboxField.NOANSWER', 'No');
	}
}
