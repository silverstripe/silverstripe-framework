<?php

namespace SilverStripe\Dev;

use SilverStripe\Core\CoreKernel;

/**
 * Kernel for running unit tests
 */
class TestKernel extends CoreKernel
{

    /** @var string[] $ciLibs */
    private $ciLibs = [];


    public function __construct($basePath)
    {
        $this->setEnvironment(self::DEV);
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


    /**
     * @param string[] $ciLibs
     */
    public function setIgnoreCILibraries(array $ciLibs): self
    {
        $this->ciLibs = $ciLibs;
        return $this;
    }

    protected function getIgnoreCILibraries(): array
    {
        return $this->ciLibs;
    }

    protected function bootErrorHandling()
    {
        // Leave phpunit to capture errors
        restore_error_handler();
    }
}
