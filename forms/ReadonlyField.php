<?php
/**
 * Read-only field to display a non-editable value with a label.
 * Consider using an {@link LabelField} if you just need a label-less
 * value display.
 * 
 * @package forms
 * @subpackage fields-basic
 */
class ReadonlyField extends FormField {

	protected $readonly = true;

	public function performReadonlyTransformation() {
		return clone $this;
	}

	public function Value() {
		if($this->value) return $this->dontEscape ? $this->value : Convert::raw2xml($this->value);
		else return '<i>(' . _t('FormField.NONE', 'none') . ')</i>';
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'type' => 'hidden',
				'value' => null,
			)
		);
	}

	public function Type() {
		return 'readonly';
	}
}
