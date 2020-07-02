<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Configurable;

class RewriteHashLinksMiddleware implements HTTPMiddleware
{
    use Configurable;

    /**
     * Mime types to be handled by this middleware
     * @var string
     * @config SilverStripe\Control\Middleware\RewriteHashLinksMiddleware.handled_mime_types
     */
    private static $handled_mime_types = [
        'text/html',
        'application/xhtml+xml',
    ];


    /**
     * Rewrites hash links in html responses
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return \SilverStripe\Control\HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var \SilverStripe\Control\HTTPResponse $response **/
        $response = $delegate($request);


        $contentType = explode(';', $response->getHeader('content-type'));
        $contentType = strtolower(trim(array_shift($contentType)));
        if (in_array($contentType, $this->config()->handled_mime_types)) {
            $body = $response->getBody();

            if (strpos($body, '<base') !== false) {
                $body = preg_replace('/(<a[^>]+href *= *)"#/i', '\\1"' . Convert::raw2att(preg_replace("/^(\\/)+/", "/", $_SERVER['REQUEST_URI'])) . '#', $body);
                $response->setBody($body);
            }
        }


        return $response;
    }
}
