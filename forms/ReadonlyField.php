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

	/**
	 * Include a hidden field in the HTML for the readonly field
	 * @var boolean
	 */
	protected $includeHiddenField = false;

	/**
	 * If true, a hidden field will be included in the HTML for the readonly field.
	 *
	 * This can be useful if you need to pass the data through on the form submission, as
	 * long as it's okay than an attacker could change the data before it's submitted.
	 *
	 * This is disabled by default as it can introduce security holes if the data is not
	 * allowed to be modified by the user.
	 *
	 * @param boolean $includeHiddenField
	 */
	public function setIncludeHiddenField($includeHiddenField) {
		$this->includeHiddenField = $includeHiddenField;
	}

	public function performReadonlyTransformation() {
		return clone $this;
	}

	/**
	 * @param array $properties
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
		// Include a hidden field in the HTML
		if($this->includeHiddenField && $this->readonly) {
			$hidden = clone $this;
			$hidden->setReadonly(false);
			return parent::Field($properties) . $hidden->Field($properties);

		} else {
			return parent::Field($properties);
		}
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
				'value' => $this->readonly ? null : $this->value,
			)
		);
	}

	public function Type() {
		return 'readonly';
	}

}
