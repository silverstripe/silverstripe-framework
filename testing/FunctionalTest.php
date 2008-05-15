<?php

/**
 * Sapphire-specific testing object designed to support functional testing of your web app.  It simulates get/post
 * requests, form submission, and can validate resulting HTML, looking up content by CSS selector.
 * 
 * The example below shows how it works.
 * 
 * <code>
 *   function testMyForm() {
 *     // Visit a URL
 *     $this->get("your/url");
 * 
 *     // Submit a form on the page that you get in response
 *     $this->submitForm("MyForm_ID",  array("Email" => "invalid email ^&*&^"));
 *
 *     // Validate the content that is returned
 *     $this->assertExactMatchBySelector("#MyForm_ID p.error", array("That email address is invalid."));
 *  }	
 * </code>
 */
class FunctionalTest extends SapphireTest {
	protected $mainSession = null;
	
	/**
	 * CSSContentParser for the most recently requested page.
	 */
	protected $cssParser = null;

	function setUp() {
		parent::setUp();
		$this->mainSession = new TestSession();
	}
	
	function tearDown() {
		parent::tearDown();
		$this->mainSession = null;
	}

	/**
	 * Submit a get request
	 */
	function get($url) {
		$this->cssParser = null;
		return $this->mainSession->get($url);
	}

	/**
	 * Submit a post request
	 */
	function post($url, $data) {
		$this->cssParser = null;
		return $this->mainSession->post($url, $data);
	}
	
	/**
	 * Submit the form with the given HTML ID, filling it out with the given data.
	 * Acts on the most recent response
	 */
	function submitForm($formID, $button = null, $data = array()) {
		$this->cssParser = null;
		return $this->mainSession->submitForm($formID, $button, $data);
	}
	
	/**
	 * Return the most recent content
	 */
	function content() {
		return $this->mainSession->lastContent();
	}
	
	/**
	 * Return a CSSContentParser for the most recent content.
	 */
	function cssParser() {
		if(!$this->cssParser) $this->cssParser = new CSSContentParser($this->mainSession->lastContent());
		return $this->cssParser;
	}
	
	/**
	 * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
	 * 
	 * The given CSS selector will be applied to the HTML of the most recent page.  The content of every matching tag
	 * will be examined.
	 * 
	 * The assertion fails if one of the expectedMatches fails to appear.
	 *
	 * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
	 */
	function assertPartialMatchBySelector($selector, $expectedMatches) {
		$items = $this->cssParser()->getBySelector($selector);
		foreach($items as $item) $actuals[$item . ''] = true;
		
		foreach($expectedMatches as $match) {
			if(!isset($actuals[$match])) {
				throw new PHPUnit_Framework_AssertionFailedError(
		            "Failed asserting the CSS selector '$selector' has an exact match to the expected elements:\n'" . implode("'\n'", $expectedMatches) . "\n\n" 
					. "Instead the following elements were found:\n'" . implode("'\n'", array_keys($actuals)) . "'"
		        );
				return;
			}

		}
	}

	/**
	 * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
	 * 
	 * The given CSS selector will be applied to the HTML of the most recent page.  The full HTML of every matching tag
	 * will be examined.
	 * 
	 * The assertion fails if one of the expectedMatches fails to appear.
	 *
	 * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
	 */
	function assertExactMatchBySelector($selector, $expectedMatches) {
		$items = $this->cssParser()->getBySelector($selector);
		foreach($items as $item) $actuals[] = $item . '';
		
		if($expectedMatches != $actuals) {
			throw new PHPUnit_Framework_AssertionFailedError(
	            "Failed asserting the CSS selector '$selector' has an exact match to the expected elements:\n'" . implode("'\n'", $expectedMatches) . "\n\n" 
				. "Instead the following elements were found:\n'" . implode("'\n'", $actuals) . "'"
	        );
		}
	}

	/**
	 * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
	 * 
	 * The given CSS selector will be applied to the HTML of the most recent page.  The content of every matching tag
	 * will be examined.
	 * 
	 * The assertion fails if one of the expectedMatches fails to appear.
	 *
	 * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
	 */
	function assertPartialHTMLMatchBySelector($selector, $expectedMatches) {
		$items = $this->cssParser()->getBySelector($selector);
		foreach($items as $item) $actuals[$item->asXML()] = true;
		
		foreach($expectedMatches as $match) {
			if(!isset($actuals[$match])) {
				throw new PHPUnit_Framework_AssertionFailedError(
		            "Failed asserting the CSS selector '$selector' has an exact match to the expected elements:\n'" . implode("'\n'", $expectedMatches) . "\n\n" 
					. "Instead the following elements were found:\n'" . implode("'\n'", array_keys($actuals)) . "'"
		        );
				return;
			}

		}
	}

	/**
	 * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
	 * 
	 * The given CSS selector will be applied to the HTML of the most recent page.  The full HTML of every matching tag
	 * will be examined.
	 * 
	 * The assertion fails if one of the expectedMatches fails to appear.
	 *
	 * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
	 */
	function assertExactHTMLMatchBySelector($selector, $expectedMatches) {
		$items = $this->cssParser()->getBySelector($selector);
		foreach($items as $item) $actuals[] = $item->asXML();
		
		if($expectedMatches != $actuals) {
			throw new PHPUnit_Framework_AssertionFailedError(
	            "Failed asserting the CSS selector '$selector' has an exact match to the expected elements:\n'" . implode("'\n'", $expectedMatches) . "\n\n" 
				. "Instead the following elements were found:\n'" . implode("'\n'", $actuals) . "'"
	        );
		}
	}	
}