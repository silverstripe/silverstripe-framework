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
		$this->document = new DOMDocument('1.0', 'UTF-8');
		$this->document->scrictErrorChecking = false;

		$this->setContent($content);
		
		parent::__construct();
	}
	
	/**
	 * @return string
	 */
	public function getContent() {
		// strip any surrounding tags before the <body> and after the </body> which are automatically added by
		// DOMDocument.  Note that we can't use the argument to saveHTML() as it's only supported in PHP 5.3.6+,
		// we support 5.3.2 as a minimum in addition to the above, trim any surrounding newlines from the output
		return trim(
			preg_replace(
				array(
					'/^<!DOCTYPE.+?>/i',
					'/(.*)<body>/i',
					'/<\/body>(.*)/i',
				),
				'',
				urldecode($this->getDocument()->saveHTML())
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
