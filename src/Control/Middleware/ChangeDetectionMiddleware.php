<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPStreamResponse;
use SilverStripe\Core\Injector\Injectable;

/**
 * Handles internal change detection via etag / ifmodifiedsince headers,
 * conditionally sending a 304 not modified if possible.
 */
class ChangeDetectionMiddleware implements HTTPMiddleware
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
        if (!$response) {
            return null;
        }

        // Ignore etag for no-store
        $cacheControl = $response->getHeader('Cache-Control');
        if ($cacheControl && strstr($cacheControl ?? '', 'no-store')) {
            return $response;
        }

        // Generate, assign, and conditionally check etag
        $etag = $this->generateETag($response);
        if ($etag) {
            $response->addHeader('ETag', $etag);

            // Check if we have a match
            $ifNoneMatch = $request->getHeader('If-None-Match');
            if ($ifNoneMatch === $etag) {
                return $this->sendNotModified($request, $response);
            }
        }

        // Check If-Modified-Since
        $ifModifiedSince = $request->getHeader('If-Modified-Since');
        $lastModified = $response->getHeader('Last-Modified');
        if ($ifModifiedSince && $lastModified && strtotime($ifModifiedSince ?? '') >= strtotime($lastModified ?? '')) {
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
        $etag = $response->getHeader('ETag');
        if ($etag) {
            return $etag;
        }

        // Skip parsing the whole body of a stream
        if ($response instanceof HTTPStreamResponse) {
            return false;
        }

        // Generate etag from body
        return sprintf('"%s"', md5($response->getBody() ?? ''));
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
