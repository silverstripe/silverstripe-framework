<?php

class EncryptField extends TextField {
	function Field() {
		return "<input class=\"text\" type=\"password\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />";
	}
}

?>