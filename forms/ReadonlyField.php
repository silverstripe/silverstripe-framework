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

	function performReadonlyTransformation() {
		return clone $this;
	}
}
?>