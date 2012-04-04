<?php
/**
 * Hidden field.
 * @package forms
 * @subpackage fields-dataless
 */
class HiddenField extends FormField {

	protected $template = 'HiddenField';

	function FieldHolder() {
		return $this->Field();
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