<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\Deprecation;

/**
 * Allows a bypass when the request has been run in CLI mode
 *
 * @deprecated 5.4.0 Will be removed without equivalent functionality to replace it in a future major release
 */
class CliBypass implements Bypass
{
    public function __construct()
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice(
                '5.4.0',
                'Will be removed without equivalent functionality to replace it in a future major release',
                Deprecation::SCOPE_CLASS
            );
        });
    }

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
