<?php

namespace SilverStripe\Framework\Test\Behaviour;

use SilverStripe\BehatExtension\Context\SilverStripeContext;
use SilverStripe\BehatExtension\Context\BasicContext;
use SilverStripe\BehatExtension\Context\LoginContext;
use SilverStripe\BehatExtension\Context\FixtureContext;
use SilverStripe\BehatExtension\Context\EmailContext;

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
		$blueprint = \Injector::inst()->create('FixtureBlueprint', 'SilverStripe\\Security\\Member');
		$blueprint->addCallback('beforeCreate', function($identifier, &$data, &$fixtures) {
			if(!isset($data['FirstName'])) $data['FirstName'] = $identifier;
		});
		$factory->define('SilverStripe\\Security\\Member', $blueprint);
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
}
