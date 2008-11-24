<?php
/**
 * Read-only field, with <label> and <span>
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