<?php

namespace SilverStripe\Framework\Test\Behaviour;

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Context\Step,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;


// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * CmsUiContext
 *
 * Context used to define steps related to SilverStripe CMS UI like Tree or Panel.
 */
class CmsUiContext extends BehatContext
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
     * @Then /^I should see the CMS$/
     */
    public function iShouldSeeTheCms()
    {
        $page = $this->getSession()->getPage();
        $cms_element = $page->find('css', '.cms');
        assertNotNull($cms_element, 'CMS not found');
    }

    /**
     * @Then /^I should see a "([^"]*)" notice$/
     */
    public function iShouldSeeANotice($notice)
    {
        $this->getMainContext()->assertElementContains('.notice-wrap', $notice);
    }

    /**
     * @Then /^I should see a "([^"]*)" message$/
     */
    public function iShouldSeeAMessage($message)
    {
        $this->getMainContext()->assertElementContains('.message', $message);
    }

    protected function getCmsTabsElement()
    {
        $this->getSession()->wait(5000, "window.jQuery('.cms-content-header-tabs').size() > 0");

        $page = $this->getSession()->getPage();
        $cms_content_header_tabs = $page->find('css', '.cms-content-header-tabs');
        assertNotNull($cms_content_header_tabs, 'CMS tabs not found');

        return $cms_content_header_tabs;
    }

    protected function getCmsContentToolbarElement()
    {
        $this->getSession()->wait(
            5000, 
            "window.jQuery('.cms-content-toolbar').size() > 0 "
            . "&& window.jQuery('.cms-content-toolbar').children().size() > 0"
        );

        $page = $this->getSession()->getPage();
        $cms_content_toolbar_element = $page->find('css', '.cms-content-toolbar');
        assertNotNull($cms_content_toolbar_element, 'CMS content toolbar not found');

        return $cms_content_toolbar_element;
    }

    protected function getCmsTreeElement()
    {
        $this->getSession()->wait(5000, "window.jQuery('.cms-tree').size() > 0");

        $page = $this->getSession()->getPage();
        $cms_tree_element = $page->find('css', '.cms-tree');
        assertNotNull($cms_tree_element, 'CMS tree not found');

        return $cms_tree_element;
    }

    protected function getGridfieldTable($title)
    {
        $page = $this->getSession()->getPage();
        $table_elements = $page->findAll('css', '.ss-gridfield-table');
        assertNotNull($table_elements, 'Table elements not found');

        $table_element = null;
        foreach ($table_elements as $table) {
            $table_title_element = $table->find('css', '.title');
            if ($table_title_element->getText() === $title) {
                $table_element = $table;
                break;
            }
        }
        assertNotNull($table_element, sprintf('Table `%s` not found', $title));

        return $table_element;
    }

    /**
     * @Given /^I should see a "([^"]*)" button in CMS Content Toolbar$/
     */
    public function iShouldSeeAButtonInCmsContentToolbar($text)
    {
        $cms_content_toolbar_element = $this->getCmsContentToolbarElement();

        $element = $cms_content_toolbar_element->find('named', array('link_or_button', "'$text'"));
        assertNotNull($element, sprintf('%s button not found', $text));
    }

    /**
     * @When /^I should see "([^"]*)" in CMS Tree$/
     */
    public function stepIShouldSeeInCmsTree($text)
    {
        $cms_tree_element = $this->getCmsTreeElement();

        $element = $cms_tree_element->find('named', array('content', "'$text'"));
        assertNotNull($element, sprintf('%s not found', $text));
    }

    /**
     * @When /^I should not see "([^"]*)" in CMS Tree$/
     */
    public function stepIShouldNotSeeInCmsTree($text)
    {
        $cms_tree_element = $this->getCmsTreeElement();

        $element = $cms_tree_element->find('named', array('content', "'$text'"));
        assertNull($element, sprintf('%s found', $text));
    }

    /**
     * @When /^I expand the "([^"]*)" CMS Panel$/
     */
    public function iExpandTheCmsPanel()
    {
        // TODO Make dynamic, currently hardcoded to first panel
        $page = $this->getSession()->getPage();

        $panel_toggle_element = $page->find('css', '.cms-content > .cms-panel > .cms-panel-toggle > .toggle-expand');
        assertNotNull($panel_toggle_element, 'Panel toggle not found');

        if ($panel_toggle_element->isVisible()) {
            $panel_toggle_element->click();
        }
    }

    /**
     * @When /^I click the "([^"]*)" CMS tab$/
     */
    public function iClickTheCmsTab($tab)
    {
        $cms_tabs_element = $this->getCmsTabsElement();

        $tab_element = $cms_tabs_element->find('named', array('link_or_button', "'$tab'"));
        assertNotNull($tab_element, sprintf('%s tab not found', $tab));

        $tab_element->click();
    }

    /**
     * @Then /^the "([^"]*)" table should contain "([^"]*)"$/
     */
    public function theTableShouldContain($table, $text)
    {
        $table_element = $this->getGridfieldTable($table);
var_dump($table_element);
        $element = $table_element->find('named', array('content', "'$text'"));
        assertNotNull($element, sprintf('Element containing `%s` not found in `%s` table', $text, $table));
    }

    /**
     * @Then /^the "([^"]*)" table should not contain "([^"]*)"$/
     */
    public function theTableShouldNotContain($table, $text)
    {
        $table_element = $this->getGridfieldTable($table);

        $element = $table_element->find('named', array('content', "'$text'"));
        assertNull($element, sprintf('Element containing `%s` not found in `%s` table', $text, $table));
    }

    /**
     * @Given /^I click on "([^"]*)" in the "([^"]*)" table$/
     */
    public function iClickOnInTheTable($text, $table)
    {
        $table_element = $this->getGridfieldTable($table);

        $element = $table_element->find('xpath', sprintf('//*[count(*)=0 and contains(.,"%s")]', $text));
        assertNotNull($element, sprintf('Element containing `%s` not found', $text));
        $element->click();
    }

    /**
     * @Then /^I can see the preview panel$/
     */
    public function iCanSeeThePreviewPanel()
    {
        $this->getMainContext()->assertElementOnPage('.cms-preview');
    }

    /**
     * @Given /^the preview contains "([^"]*)"$/
     */
    public function thePreviewContains($content)
    {
        $driver = $this->getSession()->getDriver();
        $driver->switchToIFrame('cms-preview-iframe');

        $this->getMainContext()->assertPageContainsText($content);
        $driver->switchToWindow();
    }

    /**
     * @Given /^the preview does not contain "([^"]*)"$/
     */
    public function thePreviewDoesNotContain($content)
    {
        $driver = $this->getSession()->getDriver();
        $driver->switchToIFrame('cms-preview-iframe');

        $this->getMainContext()->assertPageNotContainsText($content);
        $driver->switchToWindow();
    }
}
