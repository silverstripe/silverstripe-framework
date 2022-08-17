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


    public function __construct(string $basePath): void
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
    public function reset(): SilverStripe\Dev\TestKernel
    {
        $this->setEnvironment(self::DEV);
        $this->bootPHP();
        return $this;
    }

    protected function bootPHP(): void
    {
        parent::bootPHP();

        // Set default timezone consistently to avoid NZ-specific dependencies
        date_default_timezone_set('UTC');
    }

    protected function getIncludeTests(): bool
    {
        return true;
    }


    /**
     * Set a list of CI configurations that should cause a module's test not to be added to a manifest
     * @param string[] $ciConfigs
     */
    public function setIgnoredCIConfigs(array $ciConfigs): self
    {
        $this->ciConfigs = $ciConfigs;
        return $this;
    }

    protected function getIgnoredCIConfigs(): array
    {
        return $this->ciConfigs;
    }

    protected function bootErrorHandling(): void
    {
        // Leave phpunit to capture errors
        restore_error_handler();
    }
}
