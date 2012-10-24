<?php
/**
 * This class acts as a wrapper around the built in DOMDocument class in order to use it to manage a HTML snippet,
 * rather than a whole document, while still exposing the DOMDocument API.
 *
 * @package framework
 * @subpackage integration
 */
class SS_HTMLValue extends ViewableData {
	
	/**
	 * @var DOMDocument
	 */
	protected $document;
	
	/**
	 * @param string $content
	 */
	public function __construct($content = null) {
		$this->setDocument(new DOMDocument('1.0', 'UTF-8'));
		$this->setScrictErrorChecking(false);
		$this->setOutputFormatting(false);
		$this->setContent($content);

		parent::__construct();
	}

	/**
	 * Should strict error checking be used?
	 * @param boolean $bool
	 */
	public function setScrictErrorChecking($bool) {
		$this->getDocument()->scrictErrorChecking = $bool;
	}

	/**
	 * Should the output be formatted?
	 * @param boolean $bool
	 */
	public function setOutputFormatting($bool) {
		$this->getDocument()->formatOutput = $bool;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		// strip any surrounding tags before the <body> and after the </body> which are automatically added by
		// DOMDocument.  Note that we can't use the argument to saveHTML() as it's only supported in PHP 5.3.6+,
		// we support 5.3.2 as a minimum in addition to the above, trim any surrounding newlines from the output

		// shortcodes use square brackets which get escaped into HTML entities by saveHTML()
		// this manually replaces them back to square brackets so that the shortcodes still work correctly
		// we can't use urldecode() here, as valid characters like "+" will be incorrectly replaced with spaces
		return trim(
			preg_replace(
				array(
					'/(.*)<body>/is',
					'/<\/body>(.*)/is',
				),
				'',
				str_replace(array('%5B', '%5D'), array('[', ']'), $this->getDocument()->saveHTML())
			)
		);
	}

	/**
	 * @param string $content
	 * @return bool
	 */
	public function setContent($content) {
		// Ensure that \r (carriage return) characters don't get replaced with "&#13;" entity by DOMDocument
		// This behaviour is apparently XML spec, but we don't want this because it messes up the HTML
		$content = str_replace(chr(13), '', $content);

		return @$this->getDocument()->loadHTML(
			'<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head>' .
			"<body>$content</body></html>"
		);
	}

	/**
	 * @return DOMDocument
	 */
	public function getDocument() {
		return $this->document;
	}

	/**
	 * @param DOMDocument $document
	 */
	public function setDocument($document) {
		$this->document = $document;
	}

	/**
	 * A simple convenience wrapper around DOMDocument::getElementsByTagName().
	 *
	 * @param string $name
	 * @return DOMNodeList
	 */
	public function getElementsByTagName($name) {
		return $this->getDocument()->getElementsByTagName($name);
	}
	
	/**
	 * @see HTMLValue::getContent()
	 */
	public function forTemplate() {
		return $this->getContent();
	}
	
}
