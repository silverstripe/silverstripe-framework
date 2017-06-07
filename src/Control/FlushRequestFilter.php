<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Flushable;
use SilverStripe\Core\ClassInfo;

/**
 * Triggers a call to flush() on all implementors of Flushable.
 */
class FlushRequestFilter implements RequestFilter
{
    public function preRequest(HTTPRequest $request)
    {
        if (array_key_exists('flush', $request->getVars())) {
            foreach (ClassInfo::implementorsOf(Flushable::class) as $class) {
                $class::flush();
            }
        }
        return true;
    }

    public function postRequest(HTTPRequest $request, HTTPResponse $response)
    {
        return true;
    }
}
