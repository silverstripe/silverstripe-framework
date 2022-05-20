<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Kernel;

/**
 * Allows a bypass when the request has been run in CLI mode
 */
class CliBypass implements Bypass
{
    /**
     * Returns true if the current process is running in CLI mode
     *
     * @param HTTPRequest $request
     *
     * @return bool
     */
    public function checkRequestForBypass(HTTPRequest $request)
    {
        return Director::is_cli();
    }
}
