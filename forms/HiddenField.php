<?php
/**
 * Hidden field.
 *
 * @package forms
 * @subpackage fields-dataless
 */
class HiddenField extends FormField {

	public function FieldHolder($properties = array()) {
		return $this->Field($properties);
	}

	public function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}

	public function IsHidden() {
		return true;
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('type' => 'hidden')
		);
	}
}
