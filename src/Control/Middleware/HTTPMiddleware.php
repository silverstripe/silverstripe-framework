<?php

namespace SilverStripe\Control;

/**
 * HTTP Request middleware
 * Based on https://github.com/php-fig/fig-standards/blob/master/proposed/http-middleware/middleware.md#21-psrhttpservermiddlewaremiddlewareinterface
 */
interface HTTPMiddleware
{
    /**
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate);
}
