<?php

namespace SilverStripe\Dev;

use SilverStripe\Core\CoreKernel;

/**
 * Kernel for running unit tests
 */
class TestKernel extends CoreKernel
{
    public function __construct($basePath)
    {
        $this->setEnvironment(TestKernel::DEV);
        parent::__construct($basePath);
    }

    /**
     * Reset kernel between tests.
     * Note: this avoids resetting services (See TestState for service specific reset)
     *
     * @return $this
     */
    public function reset()
    {
        $this->setEnvironment(TestKernel::DEV);
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
