<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Confirmation;

/**
 * A rule for checking whether we need to protect a Request
 */
interface Rule
{
    /**
     * Check the request by the rule and return
     * a confirmation item
     *
     * @param HTTPRequest $request
     *
     * @return null|Confirmation\Item Confirmation item if necessary to protect the request or null otherwise
     */
    public function getRequestConfirmationItem(HTTPRequest $request);
}
