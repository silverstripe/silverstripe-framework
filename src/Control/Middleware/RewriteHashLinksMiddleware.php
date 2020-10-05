<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Configurable;

class RewriteHashLinksMiddleware implements HTTPMiddleware
{
    use Configurable;

    /**
     * Mime types to be handled by this middleware
     * @var array
     * @config SilverStripe\Control\Middleware\RewriteHashLinksMiddleware.handled_mime_types
     */
    private static $handled_mime_types = [
        'text/html',
        'application/xhtml+xml',
    ];

    /**
     * Set if hash links should be rewritten
     * @var bool
     * @config SilverStripe\Control\Middleware\RewriteHashLinksMiddleware.rewrite_hash_links
     */
    private static $rewrite_hash_links = true;

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
        
        if (!$this->config()->rewrite_hash_links) {
            return $response;
        }

        $contentType = explode(';', $response->getHeader('content-type'));
        $mimeType = strtolower(trim(array_shift($contentType)));
        if (!in_array($mimeType, $this->config()->handled_mime_types)) {
            return $response;
        }

        $body = $response->getBody();

        if (stripos($body, '<base') === false) {
            return $response;
        }
        
        $link = Convert::raw2att(preg_replace("/^(\\/)+/", "/", $_SERVER['REQUEST_URI']));
        $body = preg_replace('/(<a[^>]+href *= *)("|\')#/i', '\\1\\2' . $link . '#', $body);
        $response->setBody($body);

        return $response;
    }
}
