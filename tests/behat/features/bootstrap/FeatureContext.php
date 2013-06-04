<?php

namespace SilverStripe\Framework\Test\Behaviour;

use SilverStripe\BehatExtension\Context\SilverStripeContext,
	SilverStripe\BehatExtension\Context\BasicContext,
	SilverStripe\BehatExtension\Context\LoginContext,
	SilverStripe\BehatExtension\Context\FixtureContext,
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
class FeatureContext extends SilverStripeContext
{
	
	/**
	 * @var FixtureFactory
	 */
	protected $fixtureFactory;

	/**
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param   array   $parameters  context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters)
	{
		parent::__construct($parameters);

		$this->useContext('BasicContext', new BasicContext($parameters));
		$this->useContext('LoginContext', new LoginContext($parameters));
		$this->useContext('CmsFormsContext', new CmsFormsContext($parameters));
		$this->useContext('CmsUiContext', new CmsUiContext($parameters));

		$fixtureContext = new FixtureContext($parameters);
		$fixtureContext->setFixtureFactory($this->getFixtureFactory());
		$this->useContext('FixtureContext', $fixtureContext);
	}

	public function setMinkParameters(array $parameters)
  {
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
			$this->fixtureFactory = \Injector::inst()->get('FixtureFactory', 'FixtureContextFactory');
		}
		return $this->fixtureFactory;
	}

	public function setFixtureFactory(FixtureFactory $factory) {
		$this->fixtureFactory = $factory;
	}
}
