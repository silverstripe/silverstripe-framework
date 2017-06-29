<?php

namespace SilverStripe\Control;

/**
 * A request filter is an object that's executed before and after a
 * request occurs. By returning 'false' from the preRequest method,
 * request execution will be stopped from continuing
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 * @deprecated 4.0..5.0 Use HTTPMiddleware instead
 */
interface RequestFilter
{
    /**
     * Filter executed before a request processes
     *
     * @param HTTPRequest $request Request container object
     * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
     */
    public function preRequest(HTTPRequest $request);

    /**
     * Filter executed AFTER a request
     *
     * @param HTTPRequest $request Request container object
     * @param HTTPResponse $response
     * @return bool Whether to continue processing other filters. Null or true will continue processing (optional)
     */
    public function postRequest(HTTPRequest $request, HTTPResponse $response);
}
