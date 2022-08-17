<?php

namespace SilverStripe\Dev\State;

use SilverStripe\Core\Injector\Injectable;
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
    public function getStates(): array
    {
        return $this->states;
    }

    /**
     * @param string $name
     *
     * @return bool|TestState
     */
    public function getStateByName(string $name): SilverStripe\Dev\State\FixtureTestState
    {
        $states = $this->getStates();
        if (array_key_exists($name, $states ?? [])) {
            return $states[$name];
        }
        return false;
    }

    /**
     * @param string $class
     *
     * @return bool|TestState
     */
    public function getStateByClass($class)
    {
        $lClass = strtolower($class ?? '');
        foreach ($this->getStates() as $state) {
            if ($lClass === strtolower(get_class($state))) {
                return $state;
            }
        }
        return false;
    }

    /**
     * @param TestState[] $states
     * @return $this
     */
    public function setStates(array $states): SilverStripe\Dev\State\SapphireTestState
    {
        $this->states = $states;
        return $this;
    }

    public function setUp(SapphireTest $test): void
    {
        foreach ($this->states as $state) {
            $state->setUp($test);
        }
    }

    public function tearDown(SapphireTest $test): void
    {
        // Tear down in reverse order
        /** @var TestState $state */
        foreach (array_reverse($this->states ?? []) as $state) {
            $state->tearDown($test);
        }
    }

    public function setUpOnce(string $class): void
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
    public function tearDownOnce(string $class): void
    {
        // Tear down in reverse order
        /** @var TestState $state */
        foreach (array_reverse($this->states ?? []) as $state) {
            $state->tearDownOnce($class);
        }
    }
}
