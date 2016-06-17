<?php

namespace SilverStripe\ORM\FieldType;

use Injector;
use HTTP;
use HTMLEditorField;
use ShortcodeParser;
use TextField;

/**
 * Represents a large text field that contains HTML content.
 * This behaves similarly to {@link Text}, but the template processor won't escape any HTML content within it.
 *
 * Options can be specified in a $db config via one of the following:
 *  - "HTMLFragment(['shortcodes=true', 'whitelist=meta,link'])"
 *  - "HTMLFragment('whitelist=meta,link')"
 *  - "HTMLFragment(['shortcodes=true'])". "HTMLText" is also a synonym for this.
 *  - "HTMLFragment('shortcodes=true')"
 *
 * @see HTMLVarchar
 * @see Text
 * @see Varchar
 *
 * @package framework
 * @subpackage orm
 */
class DBHTMLText extends DBText {
	private static $escape_type = 'xml';

	private static $casting = array(
		"AbsoluteLinks" => "HTMLFragment",
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
	 * List of html properties to whitelist
	 *
	 * @var array
	 */
	protected $whitelist = [];

	/**
	 * List of html properties to whitelist
	 *
	 * @return array
	 */
	public function getWhitelist() {
		return $this->whitelist;
	}

	/**
	 * Set list of html properties to whitelist
	 *
	 * @param array $whitelist
	 * @return $this
	 */
	public function setWhitelist($whitelist) {
		if(!is_array($whitelist)) {
			$whitelist = preg_split('/\s*,\s*/', $whitelist);
		}
		$this->whitelist = $whitelist;
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

		if(array_key_exists("whitelist", $options)) {
			$this->setWhitelist($options['whitelist']);
		}

		return parent::setOptions($options);
	}

	public function RAW() {
		if ($this->processShortcodes) {
			return ShortcodeParser::get_active()->parse($this->value);
		}
		else {
			return $this->value;
		}
	}

	/**
	 * Return the value of the field with relative links converted to absolute urls (with placeholders parsed).
	 * @return string
	 */
	public function AbsoluteLinks() {
		return HTTP::absoluteURLs($this->forTemplate());
	}

	public function forTemplate() {
		// Suppress XML encoding for DBHtmlText
		return $this->RAW();
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

	public function prepValueForDB($value) {
		return parent::prepValueForDB($this->whitelistContent($value));
	}

	/**
	 * Filter the given $value string through the whitelist filter
	 *
	 * @param string $value Input html content
	 * @return string Value with all non-whitelisted content stripped (if applicable)
	 */
	public function whitelistContent($value) {
		if($this->whitelist) {
			$dom = Injector::inst()->create('HTMLValue', $value);

			$query = array();
			$textFilter = ' | //body/text()';
			foreach ($this->whitelist as $tag) {
				if($tag === 'text()') {
					$textFilter = ''; // Disable text filter if allowed
				} else {
					$query[] = 'not(self::'.$tag.')';
				}
			}

			foreach($dom->query('//body//*['.implode(' and ', $query).']'.$textFilter) as $el) {
				if ($el->parentNode) $el->parentNode->removeChild($el);
			}

			$value = $dom->getContent();
		}
		return $value;
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

		$value = $this->RAW();

		// If it's got a content tag
		if(preg_match('/<(img|embed|object|iframe|meta|source|link)[^>]*>/i', $value)) {
			return true;
		}

		// If it's just one or two tags on its own (and not the above) it's empty.
		// This might be <p></p> or <h1></h1> or whatever.
		if(preg_match('/^[\\s]*(<[^>]+>[\\s]*){1,2}$/', $value)) {
			return false;
		}

		// Otherwise its content is genuine content
		return true;
	}

	public function scaffoldFormField($title = null) {
		return new HTMLEditorField($this->name, $title);
	}

	public function scaffoldSearchField($title = null) {
		return new TextField($this->name, $title);
	}

	/**
	 * Get plain-text version
	 *
	 * @return string
	 */
	public function Plain() {
		// Preserve line breaks
		$text = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $this->RAW());

		// Convert paragraph breaks to multi-lines
		$text = preg_replace('/\<\/p\>/i', "\n\n", $text);

		// Strip out HTML tags
		$text = strip_tags($text);

		// Implode >3 consecutive linebreaks into 2
		$text = preg_replace('~(\R){2,}~', "\n\n", $text);

		// Decode HTML entities back to plain text
		return trim(\Convert::xml2raw($text));
	}

}
