<?php
/**
 * Hidden field.
 *
 * @package forms
 * @subpackage fields-dataless
 */
class HiddenField extends FormField {

	function FieldHolder($properties = array()) {
		return $this->Field($properties);
	}

	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}

	function IsHidden() {
		return true;
	}

	function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('type' => 'hidden')
		);
	}
}
