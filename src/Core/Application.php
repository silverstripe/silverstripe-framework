<?php

namespace SilverStripe\Core;

/**
 * Identifies a class as a root silverstripe application
 */
interface Application
{
    /**
     * Get the kernel for this application
     *
     * @return Kernel
     */
    public function getKernel();

    /**
     * Invoke the application control chain
     *
     * @param callable $callback
     * @param bool $flush
     * @return mixed
     */
    public function execute(callable $callback, $flush = false);
}
