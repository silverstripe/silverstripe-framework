<?php

namespace SilverStripe\Framework\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\BehatContext,
	Behat\Behat\Context\Step,
	Behat\Behat\Exception\PendingException,
	Behat\Mink\Exception\ElementNotFoundException,
	Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode,
	Behat\Mink\Element\NodeElement;


// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * CmsUiContext
 *
 * Context used to define steps related to SilverStripe CMS UI like Tree or Panel.
 */
class CmsUiContext extends BehatContext {
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
	 * @Then /^I should see the CMS$/
	 */
	public function iShouldSeeTheCms() {
		$page = $this->getSession()->getPage();
		$cms_element = $page->find('css', '.cms');
		assertNotNull($cms_element, 'CMS not found');
	}

	/**
	 * @Then /^I should see a "([^"]*)" notice$/
	 */
	public function iShouldSeeANotice($notice) {
		$this->getMainContext()->assertElementContains('.notice-wrap', $notice);
	}

	/**
	 * @Then /^I should see a "([^"]*)" message$/
	 */
	public function iShouldSeeAMessage($message) {
		$this->getMainContext()->assertElementContains('.message', $message);
	}

	protected function getCmsTabsElement() {
		$this->getSession()->wait(
			5000,
			"window.jQuery && window.jQuery('.cms-content-header-tabs').size() > 0"
		);

		$page = $this->getSession()->getPage();
		$cms_content_header_tabs = $page->find('css', '.cms-content-header-tabs');
		assertNotNull($cms_content_header_tabs, 'CMS tabs not found');

		return $cms_content_header_tabs;
	}

	protected function getCmsContentToolbarElement() {
		$this->getSession()->wait(
			5000,
			"window.jQuery && window.jQuery('.cms-content-toolbar').size() > 0 "
			. "&& window.jQuery('.cms-content-toolbar').children().size() > 0"
		);

		$page = $this->getSession()->getPage();
		$cms_content_toolbar_element = $page->find('css', '.cms-content-toolbar');
		assertNotNull($cms_content_toolbar_element, 'CMS content toolbar not found');

		return $cms_content_toolbar_element;
	}

	protected function getCmsTreeElement() {
		$this->getSession()->wait(
			5000,
			"window.jQuery && window.jQuery('.cms-tree').size() > 0"
		);

		$page = $this->getSession()->getPage();
		$cms_tree_element = $page->find('css', '.cms-tree');
		assertNotNull($cms_tree_element, 'CMS tree not found');

		return $cms_tree_element;
	}

	/**
	 * @Given /^I should see a "([^"]*)" button in CMS Content Toolbar$/
	 */
	public function iShouldSeeAButtonInCmsContentToolbar($text) {
		$cms_content_toolbar_element = $this->getCmsContentToolbarElement();

		$element = $cms_content_toolbar_element->find('named', array('link_or_button', "'$text'"));
		assertNotNull($element, sprintf('%s button not found', $text));
	}

	/**
	 * @When /^I should see "([^"]*)" in the tree$/
	 */
	public function stepIShouldSeeInCmsTree($text) {
		$cms_tree_element = $this->getCmsTreeElement();

		$element = $cms_tree_element->find('named', array('content', "'$text'"));
		assertNotNull($element, sprintf('%s not found', $text));
	}

	/**
	 * @When /^I should not see "([^"]*)" in the tree$/
	 */
	public function stepIShouldNotSeeInCmsTree($text) {
		$cms_tree_element = $this->getCmsTreeElement();

		$element = $cms_tree_element->find('named', array('content', "'$text'"));
		assertNull($element, sprintf('%s found', $text));
	}

	/**
	 * Applies a specific action to an element
	 *
	 * @param NodeElement $element Element to act on
	 * @param string $action Action, which may be one of 'hover', 'double click', 'right click', or 'left click'
	 * The default 'click' behaves the same as left click
	 */
	protected function interactWithElement($element, $action = 'click') {
		switch($action) {
			case 'hover':
				$element->mouseOver();
				break;
			case 'double click':
				$element->doubleClick();
				break;
			case 'right click':
				$element->rightClick();
				break;
			case 'left click':
			case 'click':
			default:
				$element->click();
				break;
		}

	}

	/**
	 * @When /^I (?P<method>(?:(?:double |right |left |)click)|hover) on "(?P<link>[^"]*)" in the context menu/
	 */
	public function stepIClickOnElementInTheContextMenu($method, $link) {
		$context = $this->getMainContext();
		// Wait until context menu has appeared
		$this->getSession()->wait(
			1000,
			"window.jQuery && window.jQuery('.jstree-apple-context').size() > 0"
		);
		$regionObj = $context->getRegionObj('.jstree-apple-context');
		assertNotNull($regionObj, "Context menu could not be found");

		$linkObj = $regionObj->findLink($link);
		if (empty($linkObj)) {
			throw new \Exception(sprintf(
				'The link "%s" was not found in the context menu on the page %s',
				$link,
				$this->getSession()->getCurrentUrl()
			));
		}

		$this->interactWithElement($linkObj, $method);
	}

	/**
	 * @When /^I (?P<method>(?:(?:double |right |left |)click)|hover) on "(?P<text>[^"]*)" in the tree$/
	 */
	public function stepIClickOnElementInTheTree($method, $text) {
		$treeEl = $this->getCmsTreeElement();
		$treeNode = $treeEl->findLink($text);
		assertNotNull($treeNode, sprintf('%s not found', $text));
		$this->interactWithElement($treeNode, $method);
	}

	/**
	 * @When /^I expand the "([^"]*)" CMS Panel$/
	 */
	public function iExpandTheCmsPanel() {
		//Tries to find the first visiable toggle in the page
		$page = $this->getSession()->getPage();
		$toggle_elements = $page->findAll('css', '.toggle-expand');
		assertNotNull($toggle_elements, 'Panel toggle not found');
		foreach($toggle_elements as $toggle){
			if($toggle->isVisible()){
				$toggle->click();
			}
		}
	}

	/**
	 * @When /^I (expand|collapse) "([^"]*)" in the tree$/
	 */
	public function iExpandInTheTree($action, $nodeText) {
		//Tries to find the first visiable matched Node in the page
		$page = $this->getSession()->getPage();
		$treeEl = $this->getCmsTreeElement();
		$treeNode = $treeEl->findLink($nodeText);
		assertNotNull($treeNode, sprintf('%s link not found', $nodeText));
		$cssIcon = $treeNode->getParent()->getAttribute("class");
		if($action == "expand") {
			//ensure it is collapsed
			if(false === strpos($cssIcon, 'jstree-open')) {
				$nodeIcon = $treeNode->getParent()->find('css', '.jstree-icon');
				assertTrue($nodeIcon->isVisible(), "CMS node '$nodeText' not found");
				$nodeIcon->click();
			}
		} else {
			//ensure it is expanded
			if(false === strpos($cssIcon, 'jstree-closed')) {
				$nodeIcon = $treeNode->getParent()->find('css', '.jstree-icon');
				assertTrue($nodeIcon->isVisible(), "CMS node '$nodeText' not found");
				$nodeIcon->click();
			}
		}
	}

	/**
	 * @When /^I should (not |)see a "([^"]*)" CMS tab$/
	 */
	public function iShouldSeeACmsTab($negate, $tab) {
		$this->getSession()->wait(
			5000,
			"window.jQuery && window.jQuery('.ui-tabs-nav').size() > 0"
		);

		$page = $this->getSession()->getPage();
		$tabsets = $page->findAll('css', '.ui-tabs-nav');
		assertNotNull($tabsets, 'CMS tabs not found');

		$tab_element = null;
		foreach($tabsets as $tabset) {
			$tab_element = $tabset->find('named', array('link_or_button', "'$tab'"));
			if($tab_element) break;
		}
		if($negate) {
			assertNull($tab_element, sprintf('%s tab found', $tab));
		} else {
			assertNotNull($tab_element, sprintf('%s tab not found', $tab));
		}
	}

	/**
	 * @When /^I click the "([^"]*)" CMS tab$/
	 */
	public function iClickTheCmsTab($tab) {
		$this->getSession()->wait(
			5000,
			"window.jQuery && window.jQuery('.ui-tabs-nav').size() > 0"
		);

		$page = $this->getSession()->getPage();
		$tabsets = $page->findAll('css', '.ui-tabs-nav');
		assertNotNull($tabsets, 'CMS tabs not found');

		$tab_element = null;
		foreach($tabsets as $tabset) {
			if($tab_element) continue;
			$tab_element = $tabset->find('named', array('link_or_button', "'$tab'"));
		}
		assertNotNull($tab_element, sprintf('%s tab not found', $tab));

		$tab_element->click();
	}

	/**
	 * @Then /^I can see the preview panel$/
	 */
	public function iCanSeeThePreviewPanel() {
		$this->getMainContext()->assertElementOnPage('.cms-preview');
	}

	/**
	 * @Given /^the preview contains "([^"]*)"$/
	 */
	public function thePreviewContains($content) {
		$driver = $this->getSession()->getDriver();
		// TODO Remove once we have native support in Mink and php-webdriver,
		// see https://groups.google.com/forum/#!topic/behat/QNhOuGHKEWI
		$origWindowName = $driver->getWebDriverSession()->window_handle();

		$driver->switchToIFrame('cms-preview-iframe');
		$this->getMainContext()->assertPageContainsText($content);
		$driver->switchToWindow($origWindowName);
	}

	/**
	 * @Given /^I set the CMS mode to "([^"]*)"$/
	 */
	public function iSetTheCmsToMode($mode) {
		return array(
			new Step\When(sprintf('I fill in the "Change view mode" dropdown with "%s"', $mode)),
			new Step\When('I wait for 1 second') // wait for CMS layout to redraw
		);
	}

	/**
	 * @Given /^I wait for the preview to load$/
	 */
	public function iWaitForThePreviewToLoad()  {
		$driver = $this->getSession()->getDriver();
		// TODO Remove once we have native support in Mink and php-webdriver,
		// see https://groups.google.com/forum/#!topic/behat/QNhOuGHKEWI
		$origWindowName = $driver->getWebDriverSession()->window_handle();

		$driver->switchToIFrame('cms-preview-iframe');
		$this->getSession()->wait(
			5000,
			"window.jQuery && !window.jQuery('iframe[name=cms-preview-iframe]').hasClass('loading')"
		);
		$driver->switchToWindow($origWindowName);
	}

	/**
	 * @Given /^I switch the preview to "([^"]*)"$/
	 */
	public function iSwitchThePreviewToMode($mode)  {
		$controls = $this->getSession()->getPage()->find('css', '.cms-preview-controls');
		assertNotNull($controls, 'Preview controls not found');

		$label = $controls->find('xpath', sprintf(
			'.//label[(@for="%s")]',
			$mode
		));
		assertNotNull($label, 'Preview mode switch not found');

		$label->click();

		return new Step\When('I wait for the preview to load');
	}

	/**
	 * @Given /^the preview does not contain "([^"]*)"$/
	 */
	public function thePreviewDoesNotContain($content) {
		$driver = $this->getSession()->getDriver();
		// TODO Remove once we have native support in Mink and php-webdriver,
		// see https://groups.google.com/forum/#!topic/behat/QNhOuGHKEWI
		$origWindowName = $driver->getWebDriverSession()->window_handle();

		$driver->switchToIFrame('cms-preview-iframe');
		$this->getMainContext()->assertPageNotContainsText($content);
		$driver->switchToWindow($origWindowName);
	}

	/**
	 * When I follow "my link" in preview
	 *
	 * @When /^(?:|I )follow "(?P<link>(?:[^"]|\\")*)" in preview$/
	 */
	public function clickLinkInPreview($link) {
		$driver = $this->getSession()->getDriver();
		// TODO Remove once we have native support in Mink and php-webdriver,
		// see https://groups.google.com/forum/#!topic/behat/QNhOuGHKEWI
		$origWindowName = $driver->getWebDriverSession()->window_handle();
		$driver->switchToIFrame('cms-preview-iframe');

		$link = $this->fixStepArgument($link);
		$this->getSession()->getPage()->clickLink($link);

		$driver->switchToWindow($origWindowName);
	}

	/**
	 * When I press "submit" in preview
	 *
	 * @When /^(?:|I )press "(?P<button>(?:[^"]|\\")*)" in preview$/
	 */
	public function pressButtonInPreview($button) {
		$driver = $this->getSession()->getDriver();
		// TODO Remove once we have native support in Mink and php-webdriver,
		// see https://groups.google.com/forum/#!topic/behat/QNhOuGHKEWI
		$origWindowName = $driver->getWebDriverSession()->window_handle();
		$driver->switchToIFrame('cms-preview-iframe');

		$button = $this->fixStepArgument($button);
		$this->getSession()->getPage()->pressButton($button);

		$driver->switchToWindow($origWindowName);
	}

	/**
	 * Workaround for chosen.js dropdowns or tree dropdowns which hide the original dropdown field.
	 *
	 * @When /^(?:|I )fill in the "(?P<field>(?:[^"]|\\")*)" dropdown with "(?P<value>(?:[^"]|\\")*)"$/
	 * @When /^(?:|I )fill in "(?P<value>(?:[^"]|\\")*)" for the "(?P<field>(?:[^"]|\\")*)" dropdown$/
	 */
	public function theIFillInTheDropdownWith($field, $value) {
		$field = $this->fixStepArgument($field);
		$value = $this->fixStepArgument($value);

		$nativeField = $this->getSession()->getPage()->find(
			'named',
			array('select', $this->getSession()->getSelectorsHandler()->xpathLiteral($field))
		);
		if($nativeField && $nativeField->isVisible()) {
			$nativeField->selectOption($value);
			return;
		}

		// Given the fuzzy matching, we might get more than one matching field.
		$formFields = array();

		// Find by label
		$formField = $this->getSession()->getPage()->findField($field);
		if($formField && $formField->getTagName() == 'select') {
			$formFields[] = $formField;
		}

		// Fall back to finding by title (for dropdowns without a label)
		if(!$formFields) {
			$formFields = $this->getSession()->getPage()->findAll(
				'xpath',
				sprintf(
					'//*[self::select][(./@title="%s")]',
					$field
				)
			);
		}

		// Find by name (incl. hidden fields)
		if(!$formFields) {
			$formFields = $this->getSession()->getPage()->findAll('xpath', "//*[@name='$field']");
		}

		// Find by label
		if(!$formFields) {
			$label = $this->getSession()->getPage()->find('xpath', "//label[.='$field']");
			if($label && $for = $label->getAttribute('for')) {
				$formField = $this->getSession()->getPage()->find('xpath', "//*[@id='$for']");
				if($formField) $formFields[] = $formField;
			}
		}

		assertGreaterThan(0, count($formFields), sprintf(
			'Chosen.js dropdown named "%s" not found',
			$field
		));

		// Traverse up to field holder
		$container = null;
		foreach($formFields as $formField) {
			$container = $this->findParentByClass($formField, 'field');
			if($container) break; // Default to first visible container
		}

		assertNotNull($container, 'Chosen.js field container not found');

		// Click on newly expanded list element, indirectly setting the dropdown value
		$linkEl = $container->find('xpath', './/a[./@href]');
		assertNotNull($linkEl, 'Chosen.js link element not found');
		$this->getSession()->wait(100); // wait for dropdown overlay to appear
		$linkEl->click();

		if(in_array('treedropdown', explode(' ', $container->getAttribute('class')))) {
			// wait for ajax dropdown to load
			$this->getSession()->wait(
				5000,
				"window.jQuery && "
				. "window.jQuery('#" . $container->getAttribute('id') . " .treedropdownfield-panel li').length > 0"
			);
		} else {
			// wait for dropdown overlay to appear (might be animated)
			$this->getSession()->wait(300);
		}

		$listEl = $container->find('xpath', sprintf('.//li[contains(normalize-space(string(.)), \'%s\')]', $value));
		if(null === $listEl) {
			throw new \InvalidArgumentException(sprintf(
				'Chosen.js list element with title "%s" not found',
				$value
			));
		}

		$listLinkEl = $listEl->find('xpath', './/a');
		if($listLinkEl) {
			$listLinkEl->click();
		} else {
			$listEl->click();
		}
	}

	/**
	 * Returns fixed step argument (with \\" replaced back to ").
	 *
	 * @param string $argument
	 *
	 * @return string
	 */
	protected function fixStepArgument($argument) {
		return str_replace('\\"', '"', $argument);
	}

	/**
	 * Returns the closest parent element having a specific class attribute.
	 *
	 * @param  NodeElement $el
	 * @param  String  $class
	 * @return Element|null
	 */
	protected function findParentByClass(NodeElement $el, $class) {
		$container = $el->getParent();
		while($container && $container->getTagName() != 'body') {
			if($container->isVisible() && in_array($class, explode(' ', $container->getAttribute('class')))) {
				return $container;
			}
			$container = $container->getParent();
		}

		return null;
	}
}
