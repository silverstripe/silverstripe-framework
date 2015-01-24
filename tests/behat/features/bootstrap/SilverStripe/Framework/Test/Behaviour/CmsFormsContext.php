<?php

namespace SilverStripe\Framework\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\BehatContext,
	Behat\Behat\Context\Step,
	Behat\Behat\Exception\PendingException,
	Behat\Mink\Exception\ElementHtmlException,
	Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode,
	Behat\MinkExtension\Context\MinkContext as MinkContext;
	
use Symfony\Component\DomCrawler\Crawler;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * CmsFormsContext
 *
 * Context used to define steps related to forms inside CMS.
 */
class CmsFormsContext extends BehatContext {
	protected $context;

	/**
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param   array   $parameters     context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters) {
		// Initialize your context here
		$this->context = $parameters;
	}

	/**
	 * Get Mink session from MinkContext
	 */
	public function getSession($name = null) {
		return $this->getMainContext()->getSession($name);
	}

	/**
	 * Returns fixed step argument (with \\" replaced back to ").
	 * Copied from {@see MinkContext}
	 *
	 * @param string $argument
	 * @return string
	 */
	protected function fixStepArgument($argument) {
		return str_replace('\\"', '"', $argument);
	}

	/**
	 * @Then /^I should( not? |\s*)see an edit page form$/
	 */
	public function stepIShouldSeeAnEditPageForm($negative) {
		$page = $this->getSession()->getPage();

		$form = $page->find('css', '#Form_EditForm');
		if(trim($negative)) {
			assertNull($form, 'I should not see an edit page form');
		} else {
			assertNotNull($form, 'I should see an edit page form');
		}
	}

	/**
	 * @When /^I fill in the "(?P<field>(?:[^"]|\\")*)" HTML field with "(?P<value>(?:[^"]|\\")*)"$/
	 * @When /^I fill in "(?P<value>(?:[^"]|\\")*)" for the "(?P<field>(?:[^"]|\\")*)" HTML field$/
	 */
	public function stepIFillInTheHtmlFieldWith($field, $value) {
		$field = $this->fixStepArgument($field);
		$value = $this->fixStepArgument($value);

		$page = $this->getSession()->getPage();
		$inputField = $page->findField($field);
		assertNotNull($inputField, sprintf('HTML field "%s" not found', $field));

		$this->getSession()->evaluateScript(sprintf(
			"jQuery('#%s').entwine('ss').getEditor().setContent('%s')",
			$inputField->getAttribute('id'),
			addcslashes($value, "'")
		));
	}

	/**
	 * @When /^I append "(?P<value>(?:[^"]|\\")*)" to the "(?P<field>(?:[^"]|\\")*)" HTML field$/
	 */
	public function stepIAppendTotheHtmlField($field, $value) {
		$field = $this->fixStepArgument($field);
		$value = $this->fixStepArgument($value);

		$page = $this->getSession()->getPage();
		$inputField = $page->findField($field);
		assertNotNull($inputField, sprintf('HTML field "%s" not found', $field));

		$this->getSession()->evaluateScript(sprintf(
			"jQuery('#%s').entwine('ss').getEditor().insertContent('%s')",
			$inputField->getAttribute('id'),
			addcslashes($value, "'")
		));
	}

	/**
	 * @Then /^the "(?P<locator>(?:[^"]|\\")*)" HTML field should(?P<negative> not? |\s*)contain "(?P<html>.*)"$/
	 */
	public function theHtmlFieldShouldContain($locator, $negative, $html) {
		$locator = $this->fixStepArgument($locator);
		$page = $this->getSession()->getPage();
		$element = $page->findField($locator);
		assertNotNull($element, sprintf('HTML field "%s" not found', $locator));

		$actual = $element->getValue();
		$regex = '/'.preg_quote($html, '/').'/ui';
		$failed = false;

		if(trim($negative)) {
			if (preg_match($regex, $actual)) {
				$failed = true;
			}
		} else {
			if (!preg_match($regex, $actual)) {
				$failed = true;
			}
		}

		if($failed) {
			$message = sprintf(
				'The string "%s" should%sbe found in the HTML of the element matching name "%s". Actual content: "%s"',
				$html,
				$negative,
				$locator,
				$actual
			);
			throw new ElementHtmlException($message, $this->getSession(), $element);
		}
	}

	// @codingStandardsIgnoreStart
	/**
	 * Checks formatting in the HTML field, by analyzing the HTML node surrounding
	 * the text for certain properties.
	 *
	 * Example: Given "my text" in the "Content" HTML field should be right aligned
	 * Example: Given "my text" in the "Content" HTML field should not be bold
	 *
	 * @todo Use an actual DOM parser for more accurate assertions
	 *
	 * @Given /^"(?P<text>([^"]*))" in the "(?P<field>(?:[^"]|\\")*)" HTML field should(?P<negate>(?: not)?) be (?P<formatting>(.*))$/
	 */
	public function stepContentInHtmlFieldShouldHaveFormatting($text, $field, $negate, $formatting) {
		$field = $this->fixStepArgument($field);
		$page = $this->getSession()->getPage();
		$inputField = $page->findField($field);
		assertNotNull($inputField, sprintf('HTML field "%s" not found', $field));

		$crawler = new Crawler($inputField->getValue());
		$matchedNode = null;
		foreach($crawler->filterXPath('//*') as $node) {
			if(
				$node->firstChild
				&& $node->firstChild->nodeType == XML_TEXT_NODE
				&& stripos($node->firstChild->nodeValue, $text) !== FALSE
			) {
				$matchedNode = $node;
			}
		}
		assertNotNull($matchedNode);

		$assertFn = $negate ? 'assertNotEquals' : 'assertEquals';
		if($formatting == 'bold') {
			call_user_func($assertFn, 'strong', $matchedNode->nodeName);
		} else if($formatting == 'left aligned') {
			if($matchedNode->getAttribute('style')) {
				call_user_func($assertFn, 'text-align: left;', $matchedNode->getAttribute('style'));
			}
		} else if($formatting == 'right aligned') {
			call_user_func($assertFn, 'text-align: right;', $matchedNode->getAttribute('style'));
		}
	}
	// @codingStandardsIgnoreEnd

	/**
	 * Selects the first textual match in the HTML editor. Does not support
	 * selection across DOM node boundaries.
	 *
	 * @When /^I select "(?P<text>([^"]*))" in the "(?P<field>(?:[^"]|\\")*)" HTML field$/
	 */
	public function stepIHighlightTextInHtmlField($text, $field) {
		$field = $this->fixStepArgument($field);
		$page = $this->getSession()->getPage();
		$inputField = $page->findField($field);
		assertNotNull($inputField, sprintf('HTML field "%s" not found', $field));
		$inputFieldId = $inputField->getAttribute('id');
		$text = addcslashes($text, "'");

		$js = <<<JS
// TODO <IE9 support
// TODO Allow text matches across nodes
var editor = jQuery('#$inputFieldId').entwine('ss').getEditor(),
	doc = editor.getDOM().doc,
	sel = editor.getInstance().selection,
	rng = document.createRange(),
	matched = false;

jQuery(doc).find('body *').each(function() {
	if(!matched) {
		for(var i=0;i<this.childNodes.length;i++) {
			if(!matched && this.childNodes[i].nodeValue && this.childNodes[i].nodeValue.match('$text')) {
				rng.setStart(this.childNodes[i], this.childNodes[i].nodeValue.indexOf('$text'));
				rng.setEnd(this.childNodes[i], this.childNodes[i].nodeValue.indexOf('$text') + '$text'.length);
				sel.setRng(rng);
				editor.getInstance().nodeChanged();
				matched = true;
				break;
			}
		}
	}
});
JS;

		$this->getSession()->executeScript($js);
	}

	/**
	 * Example: I should see a "Submit" button
	 * Example: I should not see a "Delete" button
	 *
	 * @Given /^I should( not? |\s*)see a "([^"]*)" button$/
	 */
	public function iShouldSeeAButton($negative, $text) {
		$page = $this->getSession()->getPage();
		$els = $page->findAll('named', array('link_or_button', "'$text'"));
		$matchedEl = null;
		foreach($els as $el) {
			if($el->isVisible()) $matchedEl = $el;
		}

		if(trim($negative)) {
			assertNull($matchedEl, sprintf('%s button found', $text));
		} else {
			assertNotNull($matchedEl, sprintf('%s button not found', $text));
		}
	}

	/**
	 * @Given /^I should( not? |\s*)see a "([^"]*)" field$/
	 */
	public function iShouldSeeAField($negative, $text) {
		$page = $this->getSession()->getPage();
		$els = $page->findAll('named', array('field', "'$text'"));
		$matchedEl = null;
		foreach($els as $el) {
			if($el->isVisible()) $matchedEl = $el;
		}

		if(trim($negative)) {
			assertNull($matchedEl);
		} else {
			assertNotNull($matchedEl);
		}
	}

}
