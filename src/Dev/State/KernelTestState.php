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
    protected function kernel()
    {
        return end($this->kernels);
    }

    /**
     * Called on setup
     *
     * @param SapphireTest $test
     */
    public function setUp(SapphireTest $test)
    {
        $this->nest();
    }

    /**
     * Called on tear down
     *
     * @param SapphireTest $test
     */
    public function tearDown(SapphireTest $test)
    {
        $this->unnest();
    }

    /**
     * Called once on setup
     *
     * @param string $class Class being setup
     */
    public function setUpOnce($class)
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
    public function tearDownOnce($class)
    {
        $this->unnest();
    }

    /**
     * Nest the current kernel
     */
    protected function nest()
    {
        // Reset state
        $this->kernel()->reset();
        $this->kernels[] = $this->kernel()->nest();
    }

    protected function unnest()
    {
        // Unnest and reset
        array_pop($this->kernels);
        $this->kernel()->activate();
        $this->kernel()->reset();
    }
}
