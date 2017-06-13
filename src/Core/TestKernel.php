<?php

namespace SilverStripe\Core;

/**
 * Kernel for running unit tests
 */
class TestKernel extends AppKernel
{
    public function __construct($flush = true)
    {
        $this->setEnvironment(self::DEV);
        parent::__construct($flush);
    }

    /**
     * Reset kernel between tests.
     * Note: this avoids resetting services (See TestState for service specific reset)
     *
     * @return $this
     */
    public function reset()
    {
        $this->setEnvironment(self::DEV);
        $this->bootPHP();
        return $this;
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

    protected function bootErrorHandling()
    {
        // Leave phpunit to capture errors
        restore_error_handler();
    }
}
