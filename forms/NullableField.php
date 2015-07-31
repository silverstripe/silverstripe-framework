<?php

/**
 * NullableField is a field that wraps other fields when you want to allow the user to specify
 * whether the value of the field is null or not.
 *
 * The classic case is to wrap a TextField so that the user can distinguish between an empty string
 * and a null string.
 *
 * $a = new NullableField(new TextField("Field1", "Field 1", "abc"));
 *
 * It displays the field that is wrapped followed by a checkbox that is used to specify if the
 * value is null or not. It uses the Title of the wrapped field for its title.
 *
 * When a form is submitted the field tests the value of the "is null" checkbox and sets its value
 * accordingly. You can retrieve the value of the wrapped field from the NullableField as follows:
 *
 * $field->Value() or $field->dataValue()
 *
 * You can specify the label to use for the "is null" checkbox. If you want to use i18n for this
 * label then specify it like this:
 *
 * $field->setIsNullLabel(_T(SOME_MODULE_ISNULL_LABEL, "Is Null"));
 *
 * @author Pete Bacon Darwin
 *
 * @package forms
 * @subpackage fields-basic
 */
class NullableField extends FormField {
	/**
	 * The field that holds the value of this field
	 *
	 * @var FormField
	 */
	protected $valueField;

	/**
	 * The label to show next to the is null check box.
	 *
	 * @var string
	 */
	protected $isNullLabel;

	/**
	 * Create a new nullable field
	 *
	 * @param FormField $valueField
	 * @param null|string $isNullLabel
	 */
	public function __construct(FormField $valueField, $isNullLabel = null) {
		$this->valueField = $valueField;

		if(isset($isNullLabel)) {
			$this->setIsNullLabel($isNullLabel);
		} else {
			$this->isNullLabel = _t('NullableField.IsNullLabel', 'Is Null');
		}

		parent::__construct(
			$valueField->getName(),
			$valueField->Title(),
			$valueField->Value()
		);

		$this->setForm($valueField->getForm());
		$this->setRightTitle($valueField->RightTitle());
		$this->setReadonly($valueField->isReadonly());
	}

	/**
	 * Get the label used for the Is Null checkbox.
	 *
	 * @return string
	 */
	public function getIsNullLabel() {
		return $this->isNullLabel;
	}

	/**
	 * Set the label used for the Is Null checkbox.
	 *
	 * @param $isNulLabel string
	 *
	 * @return $this
	 */
	public function setIsNullLabel($isNulLabel) {
		$this->isNullLabel = $isNulLabel;

		return $this;
	}

	/**
	 * Get the id used for the Is Null check box.
	 *
	 * @return string
	 */
	public function getIsNullId() {
		return $this->getName() . "_IsNull";
	}

	/**
	 * @param array $properties
	 *
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
		if($this->isReadonly()) {
			$nullableCheckbox = new CheckboxField_Readonly($this->getIsNullId());
		} else {
			$nullableCheckbox = new CheckboxField($this->getIsNullId());
		}

		$nullableCheckbox->setValue(is_null($this->dataValue()));

		return DBField::create_field('HTMLText', sprintf(
			'%s %s&nbsp;<span>%s</span>',
			$this->valueField->Field(),
			$nullableCheckbox->Field(),
			$this->getIsNullLabel()
		));
	}

	/**
	 * Value is sometimes an array, and sometimes a single value, so we need to handle both cases
	 *
	 * @param mixed $value
	 * @param null|array $data
	 *
	 * @return $this
	 */
	public function setValue($value, $data = null) {
		$id = $this->getIsNullId();

		if(is_array($data) && array_key_exists($id, $data) && $data[$id]) {
			$value = null;
		}

		$this->valueField->setValue($value);

		parent::setValue($value);

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName($name) {
		$this->valueField->setName($name);

		parent::setName($name);

		return $this;
	}

	/**
	 * @return string
	 */
	public function debug() {
		$result = sprintf(
			'%s (%s: $s : <span style="color: red">%s</span>) = ',
			$this->class,
			$this->name,
			$this->title,
			$this->message
		);

		if($this->value === null) {
			$result .= "<<null>>";
		} else {
			$result .= (string) $this->value;
		}

		return $result;
	}

}
