<?php
/**
 * Represents a short text field that is intended to contain HTML content.
 *
 * This behaves similarly to Varchar, but the template processor won't escape any HTML content within it.
 * @package framework
 * @subpackage model
 */
class HTMLVarchar extends Varchar {
	
	private static $escape_type = 'xml';

	protected $processShortcodes = true;

	public function setOptions(array $options = array()) {
		parent::setOptions($options);

		if(array_key_exists("shortcodes", $options)) {
			$this->processShortcodes = !!$options["shortcodes"];
		}
	}

	public function forTemplate() {
		if ($this->processShortcodes) {
			return ShortcodeParser::get_active()->parse($this->value);
		}
		else {
			return $this->value;
		}
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
