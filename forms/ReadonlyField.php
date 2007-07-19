<?php
/**
 * Read-only field, with <label> and <span>
 */
class ReadonlyField extends FormField {

	function performReadonlyTransformation() {
		return $this;
	}
}
?>