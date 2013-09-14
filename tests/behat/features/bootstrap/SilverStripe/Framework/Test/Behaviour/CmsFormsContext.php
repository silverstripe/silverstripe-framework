<?php

namespace SilverStripe\Framework\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\BehatContext,
	Behat\Behat\Context\Step,
	Behat\Behat\Exception\PendingException,
	Behat\Mink\Exception\ElementHtmlException,
	Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * CmsFormsContext
 *
 * Context used to define steps related to forms inside CMS.
 */
class CmsFormsContext extends BehatContext
{
	protected $context;

	/**
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param   array   $parameters     context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters)
	{
		// Initialize your context here
		$this->context = $parameters;
	}

	/**
	 * Get Mink session from MinkContext
	 */
	public function getSession($name = null)
	{
		return $this->getMainContext()->getSession($name);
	}

	/**
	 * @Then /^I should see an edit page form$/
	 */
	public function stepIShouldSeeAnEditPageForm()
	{
		$page = $this->getSession()->getPage();

		$form = $page->find('css', '#Form_EditForm');
		assertNotNull($form, 'I should see an edit page form');
	}

	/**
	 * @When /^I fill in the "(?P<field>([^"]*))" HTML field with "(?P<value>([^"]*))"$/
	 * @When /^I fill in "(?P<value>([^"]*))" for the "(?P<field>([^"]*))" HTML field$/
	 */
	public function stepIFillInTheHtmlFieldWith($field, $value)
	{
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
	 * @When /^I append "(?P<value>([^"]*))" to the "(?P<field>([^"]*))" HTML field$/
	 */
	public function stepIAppendTotheHtmlField($field, $value)
	{
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
	 * @Then /^the "(?P<locator>([^"]*))" HTML field should contain "(?P<html>.*)"$/
	 */
	public function theHtmlFieldShouldContain($locator, $html)
	{
		$page = $this->getSession()->getPage();
		$element = $page->findField($locator);
		assertNotNull($element, sprintf('HTML field "%s" not found', $locator));

		$actual = $element->getAttribute('value');
		$regex = '/'.preg_quote($html, '/').'/ui';
		if (!preg_match($regex, $actual)) {
			$message = sprintf(
				'The string "%s" was not found in the HTML of the element matching %s "%s". Actual content: "%s"', 
				$html, 
				'named', 
				$locator,
				$actual
			);
			throw new ElementHtmlException($message, $this->getSession(), $element);
		}
	}

	/**
	 * Selects the first textual match in the HTML editor. Does not support
	 * selection across DOM node boundaries.
	 * 
	 * @When /^I select "(?P<text>([^"]*))" in the "(?P<field>([^"]*))" HTML field$/
	 */
	public function stepIHighlightTextInHtmlField($text, $field)
	{
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
	if(!matched && this.firstChild && this.firstChild.nodeValue && this.firstChild.nodeValue.match('$text')) {
		rng.setStart(this.firstChild, this.firstChild.nodeValue.indexOf('$text'));
		rng.setEnd(this.firstChild, this.firstChild.nodeValue.indexOf('$text') + '$text'.length);
		sel.setRng(rng);
		editor.getInstance().nodeChanged();
		matched = true;
	}
});
JS;
		$this->getSession()->evaluateScript($js);
	}	

	/**
	 * @Given /^I should see a "([^"]*)" button$/
	 */
	public function iShouldSeeAButton($text)
	{
		$page = $this->getSession()->getPage();
		$els = $page->findAll('named', array('link_or_button', "'$text'"));
		$matchedEl = null;
		foreach($els as $el) {
			if($el->isVisible()) $matchedEl = $el;
		}
		assertNotNull($matchedEl, sprintf('%s button not found', $text));
	}

	/**
	 * @Given /^I should not see a "([^"]*)" button$/
	 */
	public function iShouldNotSeeAButton($text)
	{
		$page = $this->getSession()->getPage();
		$els = $page->findAll('named', array('link_or_button', "'$text'"));
		$matchedEl = null;
		foreach($els as $el) {
			if($el->isVisible()) $matchedEl = $el;
		}
		assertNull($matchedEl, sprintf('%s button found', $text));
	}

}
