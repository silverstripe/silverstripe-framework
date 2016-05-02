<?php

/**
 * CSSContentParser enables parsing & assertion running of HTML content via CSS selectors.
 * It works by converting the content to XHTML using tidy, rewriting the CSS selectors as XPath queries, and executing
 * those using SimpeXML.
 *
 * It was built to facilitate testing using PHPUnit and contains a number of assert methods that will throw PHPUnit
 * assertion exception when applicable.
 *
 * Tries to use the PHP tidy extension (http://php.net/tidy),
 * and falls back to the "tidy" CLI tool. If none of those exists,
 * the string is parsed directly without sanitization.
 *
 * Caution: Doesn't fully support HTML elements like <header>
 * due to them being declared illegal by the "tidy" preprocessing step.
 *
 * @package framework
 * @subpackage core
 */
class CSSContentParser extends Object {
	protected $simpleXML = null;

	public function __construct($content) {
		if(extension_loaded('tidy')) {
			// using the tidy php extension
			$tidy = new tidy();
			$tidy->parseString(
				$content,
				array(
					'output-xhtml' => true,
					'numeric-entities' => true,
					'wrap' => 0, // We need this to be consistent for functional test string comparisons
				),
				'utf8'
			);
			$tidy->cleanRepair();
			$tidy = str_replace('xmlns="http://www.w3.org/1999/xhtml"','',$tidy);
			$tidy = str_replace('&#160;','',$tidy);

		} elseif(@shell_exec('which tidy')) {
			// using tiny through cli
			$CLI_content = escapeshellarg($content);
			$tidy = `echo $CLI_content | tidy --force-output 1 -n -q -utf8 -asxhtml -w 0 2> /dev/null`;
			$tidy = str_replace('xmlns="http://www.w3.org/1999/xhtml"','',$tidy);
			$tidy = str_replace('&#160;','',$tidy);
		} else {
			// no tidy library found, hence no sanitizing
			$tidy = $content;
		}

		$this->simpleXML = @simplexml_load_string($tidy, 'SimpleXMLElement', LIBXML_NOWARNING);
		if(!$this->simpleXML) {
			throw new Exception('CSSContentParser::__construct(): Could not parse content.'
				. ' Please check the PHP extension tidy is installed.');
		}

		parent::__construct();
	}

	/**
	 * Returns a number of SimpleXML elements that match the given CSS selector.
	 * Currently the selector engine only supports querying by tag, id, and class.
	 * See {@link getByXpath()} for a more direct selector syntax.
	 *
	 * @param String $selector
	 * @return SimpleXMLElement
	 */
	public function getBySelector($selector) {
		$xpath = $this->selector2xpath($selector);
		return $this->getByXpath($xpath);
	}

	/**
	 * Allows querying the content through XPATH selectors.
	 *
	 * @param String $xpath SimpleXML compatible XPATH statement
	 * @return SimpleXMLElement|false
	 */
	public function getByXpath($xpath) {
		return $this->simpleXML->xpath($xpath);
	}

	/**
	 * Converts a CSS selector into an equivalent xpath expression.
	 * Currently the selector engine only supports querying by tag, id, and class.
	 *
	 * @param String $selector See {@link getBySelector()}
	 * @return String XPath expression
	 */
	public function selector2xpath($selector) {
		$parts = preg_split('/\\s+/', $selector);
		$xpath = "";
		foreach($parts as $part) {
			if(preg_match('/^([A-Za-z][A-Za-z0-9]*)/', $part, $matches)) {
				$xpath .= "//$matches[1]";
			} else {
				$xpath .= "//*";
			}
			$xfilters = array();
			if(preg_match('/#([^#.\[]+)/', $part, $matches)) {
				$xfilters[] = "@id='$matches[1]'";
			}
			if(preg_match('/\.([^#.\[]+)/', $part, $matches)) {
				$xfilters[] = "contains(@class,'$matches[1]')";
			}
			if($xfilters) $xpath .= '[' . implode(',', $xfilters) . ']';
		}
		return $xpath;
	}


}
