<?php
/**
 * Transformation that disables all the fields on the form.
 * @package forms
 * @subpackage transformations
 */
class DisabledTransformation extends FormTransformation {
	public function transform($field) {
		return $field->performDisabledTransformation($this);
	}
}

?>