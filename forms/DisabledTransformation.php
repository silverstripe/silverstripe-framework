<?php

class DisabledTransformation extends FormTransformation {
	public function transform($field) {
		return $field->performDisabledTransformation($this);
	}
}

?>