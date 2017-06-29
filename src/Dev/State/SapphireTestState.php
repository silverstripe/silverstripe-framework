<?php

namespace SilverStripe\Dev\State;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;

class SapphireTestState implements TestState
{
    use Injectable;

    /**
     * @var TestState[]
     */
    protected $states = [];

    /**
     * @return TestState[]
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @param TestState[] $states
     * @return $this
     */
    public function setStates(array $states)
    {
        $this->states = $states;
        return $this;
    }

    public function setUp(SapphireTest $test)
    {
        foreach ($this->states as $state) {
            $state->setUp($test);
        }
    }

    public function tearDown(SapphireTest $test)
    {
        // Tear down in reverse order
        /** @var TestState $state */
        foreach (array_reverse($this->states) as $state) {
            $state->tearDown($test);
        }
    }

    public function setUpOnce($class)
    {
        foreach ($this->states as $state) {
            $state->setUpOnce($class);
        }
    }

    /**
     * Called once on tear down
     *
     * @param string $class Class being torn down
     */
    public function tearDownOnce($class)
    {
        // Tear down in reverse order
        /** @var TestState $state */
        foreach (array_reverse($this->states) as $state) {
            $state->tearDownOnce($class);
        }
    }
}
