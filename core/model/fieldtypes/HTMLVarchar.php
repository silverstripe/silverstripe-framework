<?php
/**
 * Represents a short text field that is intended to contain HTML content.
 *
 * This behaves similarly to Varchar, but the template processor won't escape any HTML content within it.
 * @package sapphire
 * @subpackage model
 */
class HTMLVarchar extends Varchar {
	
	public function scaffoldFormField($title = null, $params = null) {
		return new HtmlEditorField($this->name, $title, 1);
	}
	
	public function scaffoldSearchField($title = null) {
		return new TextField($this->name, $title);
	}

}

?>