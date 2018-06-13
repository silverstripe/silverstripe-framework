<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injectable;

/**
 * Generates and handle responses for etag header.
 *
 * Chrome ignores Varies when redirecting back (http://code.google.com/p/chromium/issues/detail?id=79758)
 * which means that if you log out, you get redirected back to a page which Chrome then checks against
 * last-modified (which passes, getting a 304)
 * when it shouldn't be trying to use that page at all because it's the "logged in" version.
 * By also using and etag that includes both the modification date and all the varies
 * values which we also check against we can catch this and not return a 304
 */
class ETagMiddleware implements HTTPMiddleware
{
    use Injectable;

    /**
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var HTTPResponse $response */
        $response = $delegate($request);

        // Ignore etag for no-store
        $cacheControl = $response->getHeader('Cache-Control');
        if ($cacheControl && strstr($cacheControl, 'no-store')) {
            return $response;
        }

        // Generate, assign, and conditionally check etag
        $etag = $this->generateETag($response);
        if ($etag) {
            $response->addHeader('ETag', $etag);

            // Check if we have a match
            $ifNoneMatch = $request->getHeader('If-None-Match');
            if ($ifNoneMatch && $ifNoneMatch === $etag) {
                return $this->sendNotModified($request, $response);
            }
        }

        // Check If-Modified-Since
        $ifModifiedSince = $request->getHeader('If-Modified-Since');
        $lastModified = $response->getHeader('Last-Modified');
        if ($ifModifiedSince && $lastModified && strtotime($ifModifiedSince) >= strtotime($lastModified)) {
            return $this->sendNotModified($request, $response);
        }

        return $response;
    }

    /**
     * @param HTTPResponse|string $response
     * @return string|false
     */
    protected function generateETag(HTTPResponse $response)
    {
        // Existing e-tag
        if ($response instanceof HTTPResponse && $response->getHeader('ETag')) {
            return $response->getHeader('ETag');
        }

        // Generate etag from body
        $body = $response instanceof HTTPResponse
            ? $response->getBody()
            : $response;
        if ($body) {
            return sprintf('"%s"', md5($body));
        }
        return false;
    }

    /**
     * Sent not-modified response
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     * @return mixed
     */
    protected function sendNotModified(HTTPRequest $request, HTTPResponse $response)
    {
        // 304 is invalid for destructive requests
        if (in_array($request->httpMethod(), ['POST', 'DELETE', 'PUT'])) {
            $response->setStatusCode(412);
        } else {
            $response->setStatusCode(304);
        }
        $response->setBody('');
        return $response;
    }
}
