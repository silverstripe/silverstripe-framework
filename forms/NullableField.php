<?php
/**
 * NullableField is a field that wraps other fields when you want to allow the user to specify whether the value of the field is null or not.
 * 
 * The classic case is to wrap a TextField so that the user can distinguish between an empty string and a null string.
 * $a = new NullableField(new TextField("Field1", "Field 1", "abc"));
 * 
 * It displays the field that is wrapped followed by a checkbox that is used to specify if the value is null or not.
 * It uses the Title of the wrapped field for its title.
 * When a form is submitted the field tests the value of the "is null" checkbox and sets its value accordingly.
 * You can retrieve the value of the wrapped field from the NullableField as follows:
 * $field->Value() or $field->dataValue()
 * 
 * You can specify the label to use for the "is null" checkbox.  If you want to use I8N for this label then specify it like this:
 * $field->setIsNullLabel(_T(SOME_MODULE_ISNULL_LABEL, "Is Null");
 * 
 * @author Pete Bacon Darwin
 * @package forms
 * @subpackage fields-basic
 */
class NullableField extends FormField {
	/**
	 * The field that holds the value of this field
	 * @var FormField
	 */
	protected $valueField;
		
	/**
	 * The label to show next to the is null check box.
	 * @var string
	 */
	protected $isNullLabel;
	

	/**
	 * Create a new nullable field
	 * @param $valueField
	 * @return NullableField
	 */
	function __construct(FormField $valueField, $isNullLabel = null) {
		$this->valueField = $valueField;
		$this->isNullLabel = $isNullLabel;
		if ( is_null($this->isNullLabel) ) {
			// Set a default label if one is not provided.
			$this->isNullLabel = _t('NullableField.IsNullLabel', 'Is Null');
		}
		parent::__construct($valueField->getName(), $valueField->Title(), $valueField->Value(), $valueField->getForm(), $valueField->RightTitle());
		$this->readonly = $valueField->isReadonly();
	}
	
	/**
	 * Get the label used for the Is Null checkbox.
	 * @return string
	 */
	function getIsNullLabel() {
		return $this->isNullLabel;
	}
	/**
	 * Set the label used for the Is Null checkbox.
	 * @param $isNulLabel string
	 */
	function setIsNullLabel(string $isNulLabel){
		$this->isNullLabel = $isNulLabel;
		return $this;
	}
	
	/**
	 * Get the id used for the Is Null check box.
	 * @return string
	 */
	function getIsNullId() {
		return $this->getName() . "_IsNull";
	}

	/**
	 * (non-PHPdoc)
	 * @see framework/forms/FormField#Field()
	 */
	function Field($properties = array()) {
		if ( $this->isReadonly()) {
			$nullableCheckbox = new CheckboxField_Readonly($this->getIsNullId());
		} else {
			$nullableCheckbox = new CheckboxField($this->getIsNullId());
		}
		$nullableCheckbox->setValue(is_null($this->dataValue()));

		return $this->valueField->Field() . ' ' . $nullableCheckbox->Field() . '&nbsp;<span>' . $this->getIsNullLabel().'</span>';
	}

	/**
	 * Value is sometimes an array, and sometimes a single value, so we need to handle both cases
	 */
	function setValue($value, $data = null) {
		if ( is_array($data) && array_key_exists($this->getIsNullId(), $data) && $data[$this->getIsNullId()] ) {
			$value = null;
		}
		$this->valueField->setValue($value);
		parent::setValue($value);

		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see forms/FormField#setName($name)
	 */
	function setName($name) {
		// We need to pass through the name change to the underlying value field.
		$this->valueField->setName($name);
		parent::setName($name);

		return $this;
	}

	/**
	 * (non-PHPdoc)
	 * @see framework/forms/FormField#debug()
	 */
	function debug() {
		$result = "$this->class ($this->name: $this->title : <font style='color:red;'>$this->message</font>) = ";
		$result .= (is_null($this->value)) ? "<<null>>" : $this->value;
		return result;
	}
}
