<?php
/**
 * This class acts as a wrapper around the built in DOMDocument class in order to use it to manage a HTML snippet,
 * rather than a whole document, while still exposing the DOMDocument API.
 *
 * @package sapphire
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
		$content = $this->cleanContent();
		// strip the body tags from the output (which are automatically added by DOMDocument)
		return preg_replace (
			array (
				'/^\s*<body[^>]*>/i',
				'/<\/body[^>]*>\s*$/i'
			),
			null,
			$content
		);
	}
	
	/**
	 * @param string $content
	 * @return bool
	 */
	public function setContent($content) {
		return @$this->getDocument()->loadHTML(
			'<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head>' .
			"<body>$content</body></html>"
		);
	}

 	/**
	 *  Attempt to clean invalid HTML, which messes up diffs.
	 *  This checks for various methods and cleans code if possible.
	 *
	 *  NB: By default, only extremely simple tidying is performed,
	 *  by passing through DomDocument::loadHTML and saveXML
	 *  You will either need to install the php_tidy module
	 *		See: http://www.php.net/manual/en/tidy.installation.php
	 *  or else install the SilverStripe module for HTMLPurifier from:
	 *		http://svn.silverstripe.com/open/modules/htmlpurifier/trunk
	 *		See also: http://htmlpurifier.org
	 */
	protected function cleanContent() {
		$doc = $this->getDocument();
		// At most basic level of cleaning, use DOMDocument to save valid XML.
		$content = $doc->saveXML($doc->documentElement->lastChild);
		if (class_exists('Tidy')) {
			// Check for the Tidy class, provided by php-tidy
			$tidy = tidy_parse_string($content,
				array(
					'clean' => true,
					'output-xhtml'	=> true,
					'show-body-only' => true,
					'wrap' => 0,
					'input-encoding' => 'utf8',
					'output-encoding' => 'utf8'
					));
			$tidy->cleanRepair();
			$content = '' . $tidy;
		} else if (class_exists('HTMLPurifier')) {
			// Look otherwise for HTMLPurifier, provided by module.
			$html = new HTMLPurifier();
			$content = $html->purify($content);
		}
		return $content;
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
