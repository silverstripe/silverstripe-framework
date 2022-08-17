<?php

namespace SilverStripe\Dev\State;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\TestKernel;
use SilverStripe\Dev\SapphireTest;

/**
 * Handles nesting of kernel before / after tests
 */
class KernelTestState implements TestState
{
    /**
     * Stack of kernels
     *
     * @var TestKernel[]
     */
    protected $kernels = [];

    /**
     * Get active Kernel instance
     *
     * @return \SilverStripe\Dev\TestKernel
     */
    protected function kernel(): SilverStripe\Dev\TestKernel
    {
        return end($this->kernels);
    }

    /**
     * Called on setup
     *
     * @param SapphireTest $test
     */
    public function setUp(SapphireTest $test): void
    {
        $this->nest();
    }

    /**
     * Called on tear down
     *
     * @param SapphireTest $test
     */
    public function tearDown(SapphireTest $test): void
    {
        $this->unnest();
    }

    /**
     * Called once on setup
     *
     * @param string $class Class being setup
     */
    public function setUpOnce(string $class): void
    {
        // If first run, get initial kernel
        if (empty($this->kernels)) {
            $this->kernels[] = Injector::inst()->get(Kernel::class);
        }

        $this->nest();
    }

    /**
     * Called once on tear down
     *
     * @param string $class Class being torn down
     */
    public function tearDownOnce(string $class): void
    {
        $this->unnest();
    }

    /**
     * Nest the current kernel
     */
    protected function nest(): void
    {
        // Reset state
        $this->kernel()->reset();
        $this->kernels[] = $this->kernel()->nest();
    }

    protected function unnest(): void
    {
        // Unnest and reset
        array_pop($this->kernels);
        $this->kernel()->activate();
        $this->kernel()->reset();
    }
}
