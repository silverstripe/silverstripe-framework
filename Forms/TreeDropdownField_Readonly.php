<?php

namespace SilverStripe\Forms;

class TreeDropdownField_Readonly extends TreeDropdownField
{
	protected $readonly = true;

	public function Field($properties = array())
	{
		$fieldName = $this->labelField;
		if ($this->value) {
			$keyObj = $this->objectForKey($this->value);
			$obj = $keyObj ? $keyObj->$fieldName : '';
		} else {
			$obj = null;
		}

		$source = array(
			$this->value => $obj
		);

		$field = new LookupField($this->name, $this->title, $source);
		$field->setValue($this->value);
		$field->setForm($this->form);
		$field->dontEscape = true;
		return $field->Field();
	}
}
