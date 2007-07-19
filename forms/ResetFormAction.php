<?php
/**
 * Clears all fields on a form
 */
class ResetFormAction extends FormAction {
	
	function Field() {
		if($this->description) $titleAttr = "title=\"" . Convert::raw2att($this->description) . "\"";
		return "<input class=\"action\" id=\"" . $this->id() . "\" type=\"reset\" name=\"{$this->name}\" value=\"" . $this->attrTitle() . "\" $titleAttr />";
	}
	
}
?>