<?php

namespace SilverStripe\Framework\Test\Behaviour;

use SilverStripe\BehatExtension\Context\SilverStripeContext,
	SilverStripe\BehatExtension\Context\BasicContext,
	SilverStripe\BehatExtension\Context\LoginContext,
	SilverStripe\BehatExtension\Context\FixtureContext,
	SilverStripe\BehatExtension\Context\EmailContext,
	SilverStripe\Framework\Test\Behaviour\CmsFormsContext,
	SilverStripe\Framework\Test\Behaviour\CmsUiContext;

// PHPUnit
require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context
 *
 * Context automatically loaded by Behat.
 * Uses subcontexts to extend functionality.
 */
class FeatureContext extends SilverStripeContext {
	
	/**
	 * @var FixtureFactory
	 */
	protected $fixtureFactory;

	/**
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param array $parameters context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters) {
		parent::__construct($parameters);

		$this->useContext('BasicContext', new BasicContext($parameters));
		$this->useContext('LoginContext', new LoginContext($parameters));
		$this->useContext('CmsFormsContext', new CmsFormsContext($parameters));
		$this->useContext('CmsUiContext', new CmsUiContext($parameters));
		$this->useContext('EmailContext', new EmailContext($parameters));

		$fixtureContext = new FixtureContext($parameters);
		$fixtureContext->setFixtureFactory($this->getFixtureFactory());
		$this->useContext('FixtureContext', $fixtureContext);

		// Use blueprints to set user name from identifier
		$factory = $fixtureContext->getFixtureFactory();
		$blueprint = \Injector::inst()->create('FixtureBlueprint', 'Member');
		$blueprint->addCallback('beforeCreate', function($identifier, &$data, &$fixtures) {
			if(!isset($data['FirstName'])) $data['FirstName'] = $identifier;
		});
		$factory->define('Member', $blueprint);
	}

	public function setMinkParameters(array $parameters) {
		parent::setMinkParameters($parameters);
		
		if(isset($parameters['files_path'])) {
			$this->getSubcontext('FixtureContext')->setFilesPath($parameters['files_path']);	
		}
	}

	/**
	 * @return FixtureFactory
	 */
	public function getFixtureFactory() {
		if(!$this->fixtureFactory) {
			$this->fixtureFactory = \Injector::inst()->create('BehatFixtureFactory');
		}
		return $this->fixtureFactory;
	}

	public function setFixtureFactory(FixtureFactory $factory) {
		$this->fixtureFactory = $factory;
	}
	
	/**
	 * @Then /^I should( not? |\s*)see pages as a Grid$/
	 */
	public function stepIShouldSeePagesAsAGridfield($negative) {
		$page = $this->getSession()->getPage();
		$grid = $page->getFirstGridFieldTable();
		
		if(trim($negative)) {
			assertNull($grid, 'I should not see pages as a Gridfield');
		} else {
			assertNotNull($grid, 'I should see pages as a Gridfield');	
		}
	}
	
	/**
	 * @Given /^I should( not? |\s*)see pages as a bullet list$/
	 */
	public function stepIShouldSeePagesAsABulletList($negative) {
		$page = $this->getSession()->getPage();
		$ele = $page->find('css', '.cms');
		
		if(trim($negative)) {
			assertNull($ele, 'I should not see pages as a bullet list');
		} else {
			assertNotNull($ele, 'I should see pages as a bullet list');	
		}
	}
	
	/**
	 * @And /^The Filter Header component is present$/
	 */
	public function stepTheFilterHeaderComponentIsPresent($negative) {
		$page = $this->getSession()->getPage();
		$grid = $page->getFirstGridFieldTable();
		$ele = $grid->find('xpath', "//tr[contains(@class, 'filter-header')]");
		
		if(trim($negative)) {
			assertNull($ele, 'The Filter Header component is not present');
		} else {
			assertNotNull($ele, 'The Filter Header component is present');	
		}
	}
	
	/**
	 * @Then /^I should see the spyglass icon$/
	 */
	public function IShouldSeeTheSpyglassIcon($negative) {
		if(trim($negative)) {
			$this->getMainContext()->assertElementNotOnPage('.ss-gridfield-button-filter');
		} else {
			$this->getMainContext()->assertElementOnPage('.ss-gridfield-button-filter');
		}		
	}	
}
