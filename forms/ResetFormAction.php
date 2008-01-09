<?php

/**
 * @package forms
 * @subpackage actions
 */

/**
 * Action that clears all fields on a form.
 * Inserts an input tag with type=reset.
 * @package forms
 * @subpackage actions
 */
class ResetFormAction extends FormAction {
	
	function Field() {
		if($this->description) $titleAttr = "title=\"" . Convert::raw2att($this->description) . "\"";
		return "<input class=\"action\" id=\"" . $this->id() . "\" type=\"reset\" name=\"{$this->name}\" value=\"" . $this->attrTitle() . "\" $titleAttr />";
	}
	
}
?>