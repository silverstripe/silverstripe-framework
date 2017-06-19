<?php

namespace SilverStripe\Dev\State;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Resettable;
use SilverStripe\Dev\SapphireTest;

/**
 * Clears flushable / resettable objects
 */
class FlushableTestState implements TestState
{
    /**
     * @var bool
     */
    protected $flushed = false;

    public function setUp(SapphireTest $test)
    {
        // Reset all resettables
        /** @var Resettable $resettable */
        foreach (ClassInfo::implementorsOf(Resettable::class) as $resettable) {
            $resettable::reset();
        }
    }

    public function tearDown(SapphireTest $test)
    {
    }

    public function setUpOnce($class)
    {
        if ($this->flushed) {
            return;
        }
        $this->flushed = true;

        // Flush all flushable records
        /** @var Flushable $class */
        foreach (ClassInfo::implementorsOf(Flushable::class) as $class) {
            $class::flush();
        }
    }

    public function tearDownOnce($class)
    {
    }
}
