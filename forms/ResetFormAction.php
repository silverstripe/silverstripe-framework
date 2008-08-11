<?php
/**
 * Action that clears all fields on a form.
 * Inserts an input tag with type=reset.
 * @package forms
 * @subpackage actions
 */
class ResetFormAction extends FormAction {
	
	function Field() {
		$titleAttr = $this->description ? "title=\"" . Convert::raw2att($this->description) . "\"" : '';
		if($this->useButtonTag) {
			return "<button class=\"action " . $this->extraClass() . "\" id=\"" . $this->id() . "\" type=\"reset\" name=\"$this->action\" $titleAttr />" . $this->attrTitle() . "</button>\n";
		} else {
			return "<input class=\"action " . $this->extraClass() . "\" id=\"" . $this->id() . "\" type=\"reset\" name=\"$this->action\" value=\"" . $this->attrTitle() . "\" $titleAttr />\n";
		}
	}
	
}
?>