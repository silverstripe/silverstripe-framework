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
     * @When /^I fill in the content form with "([^"]*)"$/
     */
    public function stepIFillInTheContentFormWith($content)
    {
        $this->getSession()->evaluateScript("tinyMCE.get('Form_EditForm_Content').setContent('$content')");
    }

    /**
     * @Then /^the content form should contain "([^"]*)"$/
     */
    public function theContentFormShouldContain($content)
    {
        $this->getMainContext()->assertElementContains('#Form_EditForm_Content', $content);
    }
}
