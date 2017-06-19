<?php


namespace SilverStripe\View\Dev;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\State\TestState;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;

/**
 * Resets requirements for test state
 */
class RequirementsTestState implements TestState
{
    /**
     * @var Requirements_Backend
     */
    protected $backend = null;

    public function setUp(SapphireTest $test)
    {
        $this->backend = Requirements::backend();
        Requirements::set_backend(Requirements_Backend::create());
    }

    public function tearDown(SapphireTest $test)
    {
        Requirements::set_backend($this->backend);
    }

    public function setUpOnce($class)
    {
    }

    public function tearDownOnce($class)
    {
    }
}
