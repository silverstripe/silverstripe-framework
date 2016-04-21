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

	protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_TEXT;

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

	/**
	 * If $dontEscape is true the returned value will be plain text
	 * and should be escaped in templates via .XML
	 *
	 * If $dontEscape is false the returned value will be safely encoded,
	 * but should not be escaped by the frontend.
	 *
	 * @return mixed|string
	 */
	public function Value() {
		if($this->value) {
			if($this->dontEscape) {
				return $this->value;
			} else {
				Convert::raw2xml($this->value);
			}
		} else {
			$value = '(' . _t('FormField.NONE', 'none') . ')';
			if($this->dontEscape) {
				return $value;
			} else {
				return '<i>'.Convert::raw2xml($value).'</i>';
			}
		}
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
