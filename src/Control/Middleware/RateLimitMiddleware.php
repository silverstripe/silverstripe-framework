<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Cache\RateLimiter;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Security;

class RateLimitMiddleware implements HTTPMiddleware
{

    /**
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        $limiter = new RateLimiter($this->getKeyFromRequest($request), 10, 1);
        if ($limiter->canAccess()) {
            $limiter->hit();
            $response = $delegate($request);
        } else {
            $response = $this->getErrorHTTPResponse();
        }
        $this->addHeadersToResponse($response, $limiter);
        return $response;
    }

    /**
     * @param HTTPRequest $request
     * @return string
     */
    protected function getKeyFromRequest($request)
    {
        $domain = parse_url($request->getURL(), PHP_URL_HOST);
        if ($currentUser = Security::getCurrentUser()) {
            return md5($domain . '-' . $currentUser->ID);
        }
        return md5($domain . '-' . $request->getIP());
    }

    /**
     * @return HTTPResponse
     */
    protected function getErrorHTTPResponse()
    {
        $response = null;
        if (class_exists(ErrorPage::class)) {
            $response = ErrorPage::response_for(429);
        }
        return $response ?: new HTTPResponse('<h1>429 - Too many requests</h1>', 429);
    }

    /**
     * @param HTTPResponse $response
     * @param RateLimiter $limiter
     */
    protected function addHeadersToResponse($response, $limiter)
    {
        $response->addHeader('X-RateLimit-Limit', $limiter->getMaxAttempts());
        $response->addHeader('X-RateLimit-Remaining', $remaining = $limiter->getNumAttemptsRemaining());
        $ttl = $limiter->getTimeToReset();
        $response->addHeader('X-RateLimit-Reset', DBDatetime::now()->getTimestamp() + $ttl);
        if ($remaining <= 0) {
            $response->addHeader('Retry-After', $ttl);
        }
    }
}
