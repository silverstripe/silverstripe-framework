<?php

namespace SilverStripe\Dev\State;

use SilverStripe\Control\Director;
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
        $this->vars = Director::envToVars();
    }

    public function tearDown(SapphireTest $test)
    {
        Director::varsToEnv($this->vars);
    }

    public function setUpOnce($class)
    {
        $this->staticVars = Director::envToVars();
    }

    public function tearDownOnce($class)
    {
        Director::varsToEnv($this->staticVars);
    }
}
