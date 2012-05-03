<?php
/**
 * Abstract class for all fields without data.
 * Labels, headings and the like should extend from this.
 * 
 * @package forms
 * @subpackage fields-dataless
 */
class DatalessField extends FormField {
	
	/**
	 * @var bool $allowHTML
	 */
	protected $allowHTML;
	
	/**
	 * Function that returns whether this field contains data.
	 * Always returns false. 
	 */
	function hasData() { return false; }

	function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'type' => 'hidden',
			)
		);
	}
	
	/**
	 * Returns the field's representation in the form.
	 * For dataless fields, this defaults to $Field.
	 */
	function FieldHolder($properties = array()) {
		return $this->Field($properties);
	}

	/**
	 * Returns the field's representation in a field group.
	 * For dataless fields, this defaults to $Field.
	 */
	function SmallFieldHolder($properties = array()) {
		return $this->Field($properties);
	}

	/**
	 * Returns a readonly version of this field
	 */
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}
	
	/**
	 * @param bool $bool
	 */
	function setAllowHTML($bool) {
		$this->allowHTML = $bool;
		return $this;
	}
	
	/**
	 * @return bool
	 */
	function getAllowHTML() {
		return $this->allowHTML;
	}

	function Type() {
		return 'readonly';
	}

}
