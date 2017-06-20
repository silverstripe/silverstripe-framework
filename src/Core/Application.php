<?php

namespace SilverStripe\Core;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

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
     * Safely boot the application and execute the given main action
     *
     * @param HTTPRequest $request
     * @param callable $callback
     * @param bool $flush
     * @return HTTPResponse
     */
    public function execute(HTTPRequest $request, callable $callback, $flush = false);
}
