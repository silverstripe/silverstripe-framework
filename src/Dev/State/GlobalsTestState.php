<?php

namespace SilverStripe\Dev\State;

use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;

/**
 * Cleans up and reset global env vars between tests
 */
class GlobalsTestState implements TestState
{
    /**
     * Var backed up for the class
     * @var array
     */
    protected $staticVars = [];

    /**
     * Vars backed up for the test
     * @var array
     */
    protected $vars = [];

    public function setUp(SapphireTest $test)
    {
        $this->vars = Environment::getVariables();
    }

    public function tearDown(SapphireTest $test)
    {
        Environment::setVariables($this->vars);
    }

    public function setUpOnce($class)
    {
        $this->staticVars = Environment::getVariables();
    }

    public function tearDownOnce($class)
    {
        Environment::setVariables($this->staticVars);
    }
}
