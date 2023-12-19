<?php

namespace SilverStripe\Framework\Tests\Behaviour;

use BadMethodCallException;
use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ElementHtmlException;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Session;
use PHPUnit\Framework\Assert;
use SilverStripe\BehatExtension\Context\MainContextAwareTrait;
use SilverStripe\BehatExtension\Utility\StepHelper;
use Symfony\Component\DomCrawler\Crawler;
use Behat\Mink\Element\NodeElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * CmsFormsContext
 *
 * Context used to define steps related to forms inside CMS.
 */
class CmsFormsContext implements Context
{
    use MainContextAwareTrait;
    use StepHelper;

    /**
     * Get Mink session from MinkContext
     *
     * @param string $name
     * @return Session
     */
    public function getSession($name = null)
    {
        return $this->getMainContext()->getSession($name);
    }

    /**
     * Returns fixed step argument (with \\" replaced back to ").
     * Copied from {@see MinkContext}
     *
     * @param string $argument
     * @return string
     */
    protected function fixStepArgument($argument)
    {
        return str_replace('\\"', '"', $argument ?? '');
    }

    /**
     * @Then /^I should( not? |\s*)see an edit page form$/
     */
    public function stepIShouldSeeAnEditPageForm($negative)
    {
        $page = $this->getSession()->getPage();

        $form = $page->find('css', '#Form_EditForm');
        if (trim($negative ?? '')) {
            Assert::assertNull($form, 'I should not see an edit page form');
        } else {
            Assert::assertNotNull($form, 'I should see an edit page form');
        }
    }

    /**
     * @When /^I fill in the "(?P<field>(?:[^"]|\\")*)" HTML field with "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^I fill in "(?P<value>(?:[^"]|\\")*)" for the "(?P<field>(?:[^"]|\\")*)" HTML field$/
     */
    public function stepIFillInTheHtmlFieldWith($field, $value)
    {
        $inputField = $this->getHtmlField($field);
        $value = $this->fixStepArgument($value);

        $this->getSession()->evaluateScript(sprintf(
            "jQuery('#%s').entwine('ss').getEditor().setContent('%s')",
            $inputField->getAttribute('id'),
            addcslashes($value ?? '', "'")
        ));
        $this->getSession()->evaluateScript(sprintf(
            "jQuery('#%s').entwine('ss').getEditor().save()",
            $inputField->getAttribute('id')
        ));
    }

    /**
     * @When /^I append "(?P<value>(?:[^"]|\\")*)" to the "(?P<field>(?:[^"]|\\")*)" HTML field$/
     */
    public function stepIAppendTotheHtmlField($field, $value)
    {
        $inputField = $this->getHtmlField($field);
        $value = $this->fixStepArgument($value);

        $this->getSession()->evaluateScript(sprintf(
            "jQuery('#%s').entwine('ss').getEditor().insertContent('%s')",
            $inputField->getAttribute('id'),
            addcslashes($value ?? '', "'")
        ));
    }

    /**
     * @Then /^the "(?P<locator>(?:[^"]|\\")*)" HTML field should(?P<negative> not? |\s*)contain "(?P<html>.*)"$/
     */
    public function theHtmlFieldShouldContain($locator, $negative, $html)
    {
        $element = $this->getHtmlField($locator);
        $actual = $element->getValue();
        $regex = '/' . preg_quote($html ?? '', '/') . '/ui';
        $failed = false;

        if (trim($negative ?? '')) {
            if (preg_match($regex ?? '', $actual ?? '')) {
                $failed = true;
            }
        } else {
            if (!preg_match($regex ?? '', $actual ?? '')) {
                $failed = true;
            }
        }

        if ($failed) {
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
	 *
	 * @Given /^"(?P<text>([^"]*))" in the "(?P<field>(?:[^"]|\\")*)" HTML field should(?P<negate>(?: not)?) be (?P<formatting>(.*))$/
	 */
	public function stepContentInHtmlFieldShouldHaveFormatting($text, $field, $negate, $formatting) {
		$inputField = $this->getHtmlField($field);

		$crawler = new Crawler($inputField->getValue());
		$matchedNode = null;
		foreach($crawler->filterXPath('//*') as $node) {
			if(
				$node->firstChild
				&& $node->firstChild->nodeType == XML_TEXT_NODE
				&& stripos($node->firstChild->nodeValue ?? '', $text ?? '') !== FALSE
			) {
				$matchedNode = $node;
			}
		}
        Assert::assertNotNull($matchedNode);

        if ($formatting == 'bold') {
            if ($negate) {
                Assert::assertNotEquals('strong', $matchedNode->nodeName);
            } else {
                Assert::assertEquals('strong', $matchedNode->nodeName);
            }
        } else if ($formatting == 'left aligned') {
            if ($matchedNode->getAttribute('class')) {
                if ($negate) {
                    Assert::assertNotEquals('text-left', $matchedNode->getAttribute('class'));
                } else {
                    Assert::assertEquals('text-left', $matchedNode->getAttribute('class'));
                }
            }
        } else if ($formatting == 'right aligned') {
            if ($negate) {
                Assert::assertNotEquals('text-right', $matchedNode->getAttribute('class'));
            } else {
                Assert::assertEquals('text-right', $matchedNode->getAttribute('class'));
            }
        }
    }
    // @codingStandardsIgnoreEnd

    /**
     * Selects the first textual match in the HTML editor. Does not support
     * selection across DOM node boundaries.
     *
     * @When /^I select "(?P<text>([^"]*))" in the "(?P<field>(?:[^"]|\\")*)" HTML field$/
     */
    public function stepIHighlightTextInHtmlField($text, $field)
    {
        $inputField = $this->getHtmlField($field);
        $inputFieldId = $inputField->getAttribute('id');
        $text = addcslashes($text ?? '', "'");

        $js = <<<JS

var editor = jQuery('#$inputFieldId').entwine('ss').getEditor(),
	doc = editor.getInstance().getDoc(),
	sel = editor.getInstance().selection,
	rng = document.createRange(),
	matched = false;

editor.getInstance().focus();
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
     * @Given /^I should( not? |\s*)see a "([^"]*)" field$/
     */
    public function iShouldSeeAField($negative, $text)
    {
        $page = $this->getSession()->getPage();
        $els = $page->findAll('named', ['field', "'$text'"]);
        $matchedEl = null;
        /** @var NodeElement $el */
        foreach ($els as $el) {
            if ($el->isVisible()) {
                $matchedEl = $el;
            }
        }

        if (trim($negative ?? '')) {
            Assert::assertNull($matchedEl);
        } else {
            Assert::assertNotNull($matchedEl);
        }
    }

    /**
     * Click on the element with the provided CSS Selector
     *
     * @When /^I press the "([^"]*)" HTML field button$/
     */
    public function iClickOnTheHtmlFieldButton($button)
    {
        $xpath = "//*[@aria-label='" . $button . "']";
        $session = $this->getSession();
        $element = $session->getPage()->find('xpath', $xpath);
        if (null === $element) {
            // If it can't find the exact name, find one that starts with the phrase
            // Helpful for "Insert link" which has a conditional label for keyboard shortcut
            $xpath = "//*[starts-with(@aria-label, '" . $button . "')]";
            $element = $session->getPage()->find('xpath', $xpath);

            if (null === $element) {
                throw new \InvalidArgumentException(sprintf('Could not find element with xpath %s', $xpath));
            };
        }

        $element->click();
    }

    /*
     * @example Given the CMS settings has the following data
     *  | Title | My site title |
     *  | Theme | My site theme |
     * @Given /^the CMS settings have the following data$/
     */
    public function theCmsSettingsHasData(TableNode $fieldsTable)
    {
        $fields = $fieldsTable->getRowsHash();
        $siteConfig = SiteConfig::get()->first();
        foreach ($fields as $field => $value) {
            $siteConfig->$field = $value;
        }
        $siteConfig->write();
        $siteConfig->flushCache();
    }

    /**
     * Select a value in the tree dropdown field
     *
     * NOTE: This is react specific, may need to move to its own react section later
     *
     * @When /^I select "([^"]*)" in the "([^"]*)" tree dropdown$/
     */
    public function iSelectValueInTreeDropdown($text, $selector)
    {
        $page = $this->getSession()->getPage();
        /** @var NodeElement $parentElement */
        $parentElement = null;
        $this->retryThrowable(function () use (&$parentElement, &$page, $selector) {
            $parentElement = $page->find('css', $selector);
            Assert::assertNotNull($parentElement, sprintf('"%s" element not found', $selector));
            $page = $this->getSession()->getPage();
        });

        $this->retryThrowable(function () use ($parentElement, $selector) {
            $dropdown = $parentElement->find('css', '.treedropdownfield__dropdown-indicator');
            Assert::assertNotNull($dropdown, sprintf('Unable to find the dropdown in "%s"', $selector));
            $dropdown->click();
        });

        $this->retryThrowable(function () use ($text, $parentElement, $selector) {
            $element = $parentElement->find('xpath', sprintf('//*[count(*)=0 and .="%s"]', $text));
            Assert::assertNotNull($element, sprintf('"%s" not found in "%s"', $text, $selector));
            $element->click();
        });
    }

    /**
     * Locate an HTML editor field
     *
     * @param string $locator Raw html field identifier as passed from
     * @return NodeElement
     */
    protected function getHtmlField($locator)
    {
        $locator = $this->fixStepArgument($locator);
        $page = $this->getSession()->getPage();

        // Searching by name is usually good...
        $element = $page->find('css', 'textarea.htmleditor[name=\'' . $locator . '\']');

        if ($element === null) {
            $element = $this->findInputByLabelContent($locator);
        }

        Assert::assertNotNull($element, sprintf('HTML field "%s" not found', $locator));
        return $element;
    }

    protected function findInputByLabelContent($locator)
    {
        $page = $this->getSession()->getPage();
        $label = $page->findAll('xpath', sprintf('//label[contains(text(), \'%s\')]', $locator));

        if (empty($label)) {
            return null;
        }

        Assert::assertCount(1, $label, sprintf(
            'Found more than one element containing the phrase "%s".',
            $locator
        ));

        $label = array_shift($label);

        $fieldId = $label->getAttribute('for');
        return $page->find('css', '#' . $fieldId);
    }

    /**
     * @Given /^the "([^"]*)" field ((?:does not have)|(?:has)) property "([^"]*)"$/
     */
    public function assertTheFieldHasProperty($name, $cond, $property)
    {
        $name = $this->fixStepArgument($name);
        $property = $this->fixStepArgument($property);

        $context = $this->getMainContext();
        $fieldObj = $context->assertSession()->fieldExists($name);

        // Check property
        $hasProperty = $fieldObj->hasAttribute($property);
        switch ($cond) {
            case 'has':
                assert($hasProperty, "Field $name does not have property $property");
                break;
            case 'does not have':
                assert(!$hasProperty, "Field $name should not have property $property");
                break;
            default:
                throw new BadMethodCallException("Invalid condition");
        }
    }

    /**
     * @When /^I switch to the "([^"]*)" iframe$/
     * @param string $id iframe id property
     */
    public function stepSwitchToTheFrame($id)
    {
        $this->getMainContext()->getSession()->getDriver()->switchToIFrame($id);
    }

    /**
     * @When /^I am not in an iframe$/
     */
    public function stepSwitchToParentFrame()
    {
        $this->getMainContext()->getSession()->getDriver()->switchToIFrame(null);
    }

    /**
     * @When /^my session expires$/
     */
    public function stepMySessionExpires()
    {
        // Destroy cookie to detach session
        $this->getMainContext()->getSession()->setCookie('PHPSESSID', null);
    }

    /**
     * @When /^I should see the "([^"]*)" button in the "([^"]*)" gridfield for the "([^"]*)" row$/
     * @param string $buttonLabel
     * @param string $gridFieldName
     * @param string $rowName
     */
    public function assertIShouldSeeTheGridFieldButtonForRow($buttonLabel, $gridFieldName, $rowName)
    {
        $button = $this->getGridFieldButton($gridFieldName, $rowName, $buttonLabel);
        Assert::assertNotNull($button, sprintf('Button "%s" not found', $buttonLabel));
    }

    /**
     * @When /^I should not see the "([^"]*)" button in the "([^"]*)" gridfield for the "([^"]*)" row$/
     * @param string $buttonLabel
     * @param string $gridFieldName
     * @param string $rowName
     */
    public function assertIShouldNotSeeTheGridFieldButtonForRow($buttonLabel, $gridFieldName, $rowName)
    {
        $button = $this->getGridFieldButton($gridFieldName, $rowName, $buttonLabel);
        Assert::assertNull($button, sprintf('Button "%s" found', $buttonLabel));
    }

    /**
     * @When /^I click the "([^"]*)" button in the "([^"]*)" gridfield for the "([^"]*)" row$/
     * @param string $buttonLabel
     * @param string $gridFieldName
     * @param string $rowName
     */
    public function stepIClickTheGridFieldButtonForRow($buttonLabel, $gridFieldName, $rowName)
    {
        $button = $this->getGridFieldButton($gridFieldName, $rowName, $buttonLabel);
        Assert::assertNotNull($button, sprintf('Button "%s" not found', $buttonLabel));

        $button->click();
    }

    /**
     * @When /^I click the "([^"]*)" button in the "([^"]*)" gridfield for the "([^"]*)" row, confirming the dialog$/
     * @param string $buttonLabel
     * @param string $gridFieldName
     * @param string $rowName
     */
    public function stepIClickTheGridFieldButtonForRowConfirmingDialog($buttonLabel, $gridFieldName, $rowName)
    {
        $this->stepIClickTheGridFieldButtonForRow($buttonLabel, $gridFieldName, $rowName);
        $session = $this->getSession()->getDriver()->getWebDriver();
        $session->wait()->until(
            WebDriverExpectedCondition::alertIsPresent(),
            "Alert is expected"
        );
        $session->switchTo()->alert()->accept();
    }

    /**
     * Finds a button in the gridfield row
     *
     * @param $gridFieldName
     * @param $rowName
     * @param $buttonLabel
     * @return $button
     */
    protected function getGridFieldButton($gridFieldName, $rowName, $buttonLabel)
    {
        $page = $this->getSession()->getPage();
        $gridField = $page->find('xpath', sprintf('//*[@data-name="%s"]', $gridFieldName));
        Assert::assertNotNull($gridField, sprintf('Gridfield "%s" not found', $gridFieldName));

        $name = $gridField->find('xpath', sprintf('//*[count(*)=0 and contains(.,"%s")]', $rowName));
        if (!$name) {
            return null;
        }

        if ($dropdownButton = $name->getParent()->find('css', '.action-menu__toggle')) {
            $dropdownButton->click();
        }

        $button = $name->getParent()->find('named', ['link_or_button', $buttonLabel]);

        return $button;
    }

    /**
     * @When /^I click the "([^"]*)" option in the "([^"]*)" listbox$/
     * @param $optionLabel
     * @param $fieldName
     */
    public function stepIClickTheListBoxOption($optionLabel, $fieldName)
    {
        $page = $this->getSession()->getPage();
        $listBox = $page->find('xpath', sprintf('//*[@name="%s[]"]', $fieldName));
        Assert::assertNotNull($listBox, sprintf('The listbox %s is not found', $fieldName));

        $option = $listBox->getParent()
            ->find('css', '.chosen-choices')
            ->find('xpath', sprintf('//*[count(*)=0 and contains(.,"%s")]', $optionLabel));
        Assert::assertNotNull($option, sprintf('Option %s is not found', $optionLabel));

        $button = $option->getParent()->find('css', 'a');

        $button->click();
    }
}
