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

	static function create($name) {
		return new HiddenField($name);
	}

}