<?php

/**
 * @package forms
 * @subpackage fields-basic
 */

/**
 * Read-only field, with <label> and <span>
 * @package forms
 * @subpackage fields-basic
 */
class ReadonlyField extends FormField {

	function performReadonlyTransformation() {
		return $this;
	}
}
?>