<?php
/**
 * Single checkbox field.
 *
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxField extends FormField {

	function setValue($value) {
		$this->value = ($value) ? 1 : 0;
		return $this;
	}

	function dataValue() {
		return ($this->value) ? 1 : NULL;
	}

	function Value() {
		return ($this->value) ? 1 : 0;
	}

	function getAttributes() {
		$attrs = parent::getAttributes();
		$attrs['value'] = 1;
		return array_merge(
			$attrs,
			array(
				'checked' => ($this->Value()) ? 'checked' : null,
				'type' => 'checkbox',
			)
		);
	}

	/**
	 * Returns a readonly version of this field
	 */
	function performReadonlyTransformation() {
		$field = new CheckboxField_Readonly($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		return $field;	
	}
	
	function performDisabledTransformation() {
		$clone = clone $this;
		$clone->setDisabled(true);
		return $clone;
	}

}

/**
 * Readonly version of a checkbox field - "Yes" or "No".
 *
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxField_Readonly extends ReadonlyField {

	function performReadonlyTransformation() {
		return clone $this;
	}

	function Value() {
		return Convert::raw2xml($this->value ? _t('CheckboxField.YES', 'Yes') : _t('CheckboxField.NO', 'No'));
	}

}
