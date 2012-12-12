<?php

namespace SilverStripe\Framework\Test\Behaviour;

use SilverStripe\BehatExtension\Context\SilverStripeContext,
	SilverStripe\BehatExtension\Context\BasicContext,
	SilverStripe\BehatExtension\Context\LoginContext,
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
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param   array   $parameters  context parameters (set them up through behat.yml)
	 */
	public function __construct(array $parameters)
	{
		$this->useContext('BasicContext', new BasicContext($parameters));
		$this->useContext('LoginContext', new LoginContext($parameters));
		$this->useContext('CmsFormsContext', new CmsFormsContext($parameters));
		$this->useContext('CmsUiContext', new CmsUiContext($parameters));

		parent::__construct($parameters);
	}
}
