<?php

namespace SilverStripe\Dev;

use SilverStripe\Core\CoreKernel;

/**
 * Kernel for running unit tests
 */
class TestKernel extends CoreKernel
{

    /** @var string[] $ciConfigs */
    private $ciConfigs = [];


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
     * Set a list of CI configurations that should cause a module's test not to be added to a manifest
     * @param string[] $ciConfigs
     * @deprecated 4.12.0 Will be removed without equivalent functionality
     */
    public function setIgnoredCIConfigs(array $ciConfigs): self
    {
        Deprecation::notice('4.12.0', 'Will be removed without equivalent functionality');

        $this->ciConfigs = $ciConfigs;
        return $this;
    }

    /**
     * @deprecated 4.12.0 Will be removed without equivalent functionality
     */
    protected function getIgnoredCIConfigs(): array
    {
        Deprecation::notice('4.12.0', 'Will be removed without equivalent functionality');

        return $this->ciConfigs;
    }

    protected function bootErrorHandling()
    {
        // Leave phpunit to capture errors
        restore_error_handler();
    }
}
