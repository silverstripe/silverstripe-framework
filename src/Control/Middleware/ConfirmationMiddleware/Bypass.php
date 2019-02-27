<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Confirmation;

/**
 * A bypass for manual confirmation by user (depending on some runtime conditions)
 */
interface Bypass
{
    /**
     * Check the request for whether we can bypass
     * the confirmation
     *
     * @param HTTPRequest $request
     *
     * @return bool True if we can bypass, False if the confirmation is required
     */
    public function checkRequestForBypass(HTTPRequest $request);
}
