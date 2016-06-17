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

	private static $casting = array(
		// DBString conversion / summary methods
		// Not overridden, but returns HTML instead of plain text.
		"LowerCase" => "HTMLFragment",
		"UpperCase" => "HTMLFragment",
	);

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
		// Suppress XML encoding for DBHtmlText
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
			str_replace(']]>', ']]]]><![CDATA[>', $this->RAW())
		);
	}

	/**
	 * Get plain-text version.
	 *
	 * Note: unlike DBHTMLText, this doesn't respect line breaks / paragraphs
	 *
	 * @return string
	 */
	public function Plain() {
		// Strip out HTML
		$text = strip_tags($this->RAW());

		// Convert back to plain text
		return trim(\Convert::xml2raw($text));
	}

	/**
	 * Returns true if the field has meaningful content.
	 * Excludes null content like <h1></h1>, <p></p> ,etc
	 *
	 * @return boolean
	 */
	public function exists() {
		// If it's blank, it's blank
		if(!parent::exists()) {
			return false;
		}

		// If it's got a content tag
		if(preg_match('/<(img|embed|object|iframe|meta|source|link)[^>]*>/i', $this->RAW())) {
			return true;
		}

		// If it's just one or two tags on its own (and not the above) it's empty.
		// This might be <p></p> or <h1></h1> or whatever.
		if(preg_match('/^[\\s]*(<[^>]+>[\\s]*){1,2}$/', $this->RAW())) {
			return false;
		}

		// Otherwise its content is genuine content
		return true;
	}

	public function scaffoldFormField($title = null) {
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
