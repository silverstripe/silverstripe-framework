<?php

/**
 * This class handles the converting of HTML fragments between a string and a DOMDocument based
 * representation.
 *
 * It's designed to allow dependancy injection to replace the standard HTML4 version with one that
 * handles XHTML or HTML5 instead
 *
 * @package framework
 * @subpackage integration
 */
abstract class SS_HTMLValue extends ViewableData {

	public function __construct($fragment = null) {
		if ($fragment) $this->setContent($fragment);
		parent::__construct();
	}

	abstract public function setContent($fragment);

	/**
	 * @param string $content
	 * @return string
	 */
	public function getContent() {
		$doc = clone $this->getDocument();
		$xp = new DOMXPath($doc);

		// If there's no body, the content is empty string
		if (!$doc->getElementsByTagName('body')->length) return '';

		// saveHTML Percentage-encodes any URI-based attributes. We don't want this, since it interferes with
		// shortcodes. So first, save all the attribute values for later restoration.
		$attrs = array(); $i = 0;

		foreach ($xp->query('//body//@*') as $attr) {
			$key = "__HTMLVALUE_".($i++);
			$attrs[$key] = $attr->value;
			$attr->value = $key;
		}

		// Then, call saveHTML & extract out the content from the body tag
		$res = preg_replace(
			array(
				'/^(.*?)<body>/is',
				'/<\/body>(.*?)$/isD',
			),
			'',
			$doc->saveHTML()
		);

		// Then replace the saved attributes with their original versions
		$res = preg_replace_callback('/__HTMLVALUE_(\d+)/', function($matches) use ($attrs) {
			return Convert::raw2att($attrs[$matches[0]]);
		}, $res);

		return $res;
	}

	/** @see HTMLValue::getContent() */
	public function forTemplate() {
		return $this->getContent();
	}

	/** @var DOMDocument */
	private $document = null;
	/** @var bool */
	private $valid = true;

	/**
	 * Get the DOMDocument for the passed content
	 * @return DOMDocument | false - Return false if HTML not valid, the DOMDocument instance otherwise
	 */
	public function getDocument() {
		if (!$this->valid) {
			return false;
		}
		else if ($this->document) {
			return $this->document;
		}
		else {
			$this->document = new DOMDocument('1.0', 'UTF-8');
			$this->document->strictErrorChecking = false;
			$this->document->formatOutput = false;

			return $this->document;
		}
	}

	/**
	 * Is this HTMLValue in an errored state?
	 * @return bool
	 */
	public function isValid() {
		return $this->valid;
	}

	/**
	 * @param DOMDocument $document
	 */
	public function setDocument($document) {
		$this->document = $document;
		$this->valid = true;
	}

	public function setInvalid() {
		$this->document = $this->valid = false;
	}

	/**
	 * Pass through any missed method calls to DOMDocument (if they exist)
	 * so that HTMLValue can be treated mostly like an instance of DOMDocument
	 */
	public function __call($method, $arguments) {
		$doc = $this->getDocument();

		if(method_exists($doc, $method)) {
			return call_user_func_array(array($doc, $method), $arguments);
		}
		else {
			return parent::__call($method, $arguments);
		}
	}

	/**
	 * Get the body element, or false if there isn't one (we haven't loaded any content
	 * or this instance is in an invalid state)
	 */
	public function getBody() {
		$doc = $this->getDocument();
		if (!$doc) return false;

		$body = $doc->getElementsByTagName('body');
		if (!$body->length) return false;

		return $body->item(0);
	}

	/**
	 * Make an xpath query against this HTML
	 *
	 * @param $query string - The xpath query string
	 * @return DOMNodeList
	 */
	public function query($query) {
		$xp = new DOMXPath($this->getDocument());
		return $xp->query($query);
	}
}

class SS_HTML4Value extends SS_HTMLValue {

	/**
	 * @param string $content
	 * @return bool
	 */
	public function setContent($content) {
		// Ensure that \r (carriage return) characters don't get replaced with "&#13;" entity by DOMDocument
		// This behaviour is apparently XML spec, but we don't want this because it messes up the HTML
		$content = str_replace(chr(13), '', $content);

		// Reset the document if we're in an invalid state for some reason
		if (!$this->isValid()) $this->setDocument(null);

		$errorState = libxml_use_internal_errors(true);
		$result = $this->getDocument()->loadHTML(
			'<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head>' .
			"<body>$content</body></html>"
		);
		libxml_clear_errors();
		libxml_use_internal_errors($errorState);
		return $result;
	}
}
