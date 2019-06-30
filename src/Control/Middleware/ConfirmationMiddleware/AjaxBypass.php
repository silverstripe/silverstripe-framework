<?php declare(strict_types = 1);

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;

/**
 * Bypass for AJAX requests
 *
 * Relies on HTTPRequest::isAjax implementation
 */
class AjaxBypass implements Bypass
{
    /**
     * Returns true for AJAX requests
     *
     * @param HTTPRequest $request
     *
     * @return bool
     */
    public function checkRequestForBypass(HTTPRequest $request)
    {
        return $request->isAjax();
    }
}
