<?php
/**
 * Represents a short text field that is intended to contain HTML content.
 *
 * This behaves similarly to Varchar, but the template processor won't escape any HTML content within it.
 * @package framework
 * @subpackage model
 */
class HTMLVarchar extends Varchar {
	
	public static $escape_type = 'xml';
	
	public function forTemplate() {
		return ShortcodeParser::get_active()->parse($this->value);
	}
	
	public function exists() {
		return parent::exists() && $this->value != '<p></p>';
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new HtmlEditorField($this->name, $title, 1);
	}
	
	public function scaffoldSearchField($title = null) {
		return new TextField($this->name, $title);
	}
	
}
