<?php

namespace SilverStripe\ORM\FieldType;

use ShortcodeParser;
use HTMLEditorField;
use TextField;

/**
 * Represents a short text field that is intended to contain HTML content.
 *
 * This behaves similarly to Varchar, but the template processor won't escape any HTML content within it.
 * @package framework
 * @subpackage orm
 */
class DBHTMLVarchar extends DBVarchar {

	private static $escape_type = 'xml';

	/**
	 * Enable shortcode parsing on this field
	 *
	 * @var bool
	 */
	protected $processShortcodes = false;

	/**
	 * Check if shortcodes are enabled
	 *
	 * @return bool
	 */
	public function getProcessShortcodes() {
		return $this->processShortcodes;
	}

	/**
	 * Set shortcodes on or off by default
	 *
	 * @param bool $process
	 * @return $this
	 */
	public function setProcessShortcodes($process) {
		$this->processShortcodes = (bool)$process;
		return $this;
	}
	/**
	 * @param array $options
	 *
	 * Options accepted in addition to those provided by Text:
	 *
	 *   - shortcodes: If true, shortcodes will be turned into the appropriate HTML.
	 *                 If false, shortcodes will not be processed.
	 *
	 *   - whitelist: If provided, a comma-separated list of elements that will be allowed to be stored
	 *                (be careful on relying on this for XSS protection - some seemingly-safe elements allow
	 *                attributes that can be exploited, for instance <img onload="exploiting_code();" src="..." />)
	 *                Text nodes outside of HTML tags are filtered out by default, but may be included by adding
	 *                the text() directive. E.g. 'link,meta,text()' will allow only <link /> <meta /> and text at
	 *                the root level.
	 *
	 * @return $this
	 */
	public function setOptions(array $options = array()) {
		if(array_key_exists("shortcodes", $options)) {
			$this->setProcessShortcodes(!!$options["shortcodes"]);
		}

		return parent::setOptions($options);
	}

	public function forTemplate() {
		return $this->RAW();
	}

	public function RAW() {
		if ($this->processShortcodes) {
			return ShortcodeParser::get_active()->parse($this->value);
		} else {
			return $this->value;
		}
	}

	/**
	 * Safely escape for XML string
	 *
	 * @return string
	 */
	public function CDATA() {
		return sprintf(
			'<![CDATA[%s]]>',
			str_replace(']]>', ']]]]><![CDATA[>', $this->forTemplate())
		);
	}

	public function exists() {
		return parent::exists() && $this->RAW() != '<p></p>';
	}

	public function scaffoldFormField($title = null, $params = null) {
		return new HTMLEditorField($this->name, $title, 1);
	}

	public function scaffoldSearchField($title = null) {
		return new TextField($this->name, $title);
	}

	/**
	 * @return string
	 */
	public function NoHTML()
	{
		// Preserve line breaks
		$text = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $this->RAW());
		// Convert back to plain text
		return \Convert::xml2raw(strip_tags($text));
	}

}
