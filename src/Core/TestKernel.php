<?php

namespace SilverStripe\Core;

/**
 * Kernel for running unit tests
 */
class TestKernel extends AppKernel
{
    public function __construct($flush = true)
    {
        parent::__construct($flush);
        $this->setEnvironment(self::DEV);
    }

    /**
     * Reset kernel between tests.
     * Note: this avoids resetting services (See TestState for service specific reset)
     */
    public function reset()
    {
        $this->setEnvironment(self::DEV);
        $this->bootPHP();
    }

    protected function bootPHP()
    {
        parent::bootPHP();

        // Set default timezone consistently to avoid NZ-specific dependencies
        date_default_timezone_set('UTC');
    }

    protected function getIncludeTests()
    {
        return true;
    }
}
