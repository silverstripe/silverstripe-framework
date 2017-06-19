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
}
