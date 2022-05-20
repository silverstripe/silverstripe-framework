<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\Middleware\CanonicalURLMiddleware;

class BasicAuthMiddleware implements HTTPMiddleware
{
    /**
     * URL Patterns for basic auth. Keys are the Regexp string to match, and the key can
     * be one of the below:
     *  - true (bool) - Enabled for this url
     *  - false (bool) - Disabled for this url
     *  - Any string / array - Enabled for this url, and require the given string as a permission code
     *  - null (default) - Calls BasicAuth::protect_site_if_necessary(), which falls back to config setting
     *
     * E.g.
     * [
     *   '#^home#i' => false,
     *   '#^secure#i' => true,
     *   '#^secure/admin#i' => 'ADMIN',
     * ]
     *
     * @see CanonicalURLMiddleware
     * @var array
     */
    protected $urlPatterns = [];

    /**
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        // Check if url matches any patterns
        $match = $this->checkMatchingURL($request);

        // Check middleware unless specifically opting out
        if ($match !== false) {
            try {
                // Determine method to check
                if ($match) {
                    // Truthy values are explicit, check with optional permission code
                    $permission = $match === true ? null : $match;
                    BasicAuth::requireLogin(
                        $request,
                        BasicAuth::config()->get('entire_site_protected_message'),
                        $permission,
                        false
                    );
                } elseif ($match === null) {
                    // Null implies fall back to default behaviour
                    BasicAuth::protect_site_if_necessary($request);
                }
            } catch (HTTPResponse_Exception $ex) {
                return $ex->getResponse();
            }
        }

        // Pass on to other middlewares
        return $delegate($request);
    }

    /**
     * Get list of url patterns
     *
     * @return array
     */
    public function getURLPatterns()
    {
        return $this->urlPatterns ?: [];
    }

    /**
     * @param array $urlPatterns
     * @return $this
     */
    public function setURLPatterns(array $urlPatterns)
    {
        $this->urlPatterns = $urlPatterns;
        return $this;
    }

    /**
     * Check if global basic auth is enabled for the given request
     *
     * @param HTTPRequest $request
     * @return bool|string|array|null boolean value if enabled/disabled explicitly for this request,
     * or null if should fall back to config value. Can also provide an explicit string / array of permission
     * codes to require for this requset.
     */
    protected function checkMatchingURL(HTTPRequest $request)
    {
        // Null if no permissions enabled
        $patterns = $this->getURLPatterns();
        if (!$patterns) {
            return null;
        }

        // Filter redirect based on url
        $relativeURL = $request->getURL(true);
        foreach ($patterns as $pattern => $result) {
            if (preg_match($pattern ?? '', $relativeURL ?? '')) {
                return $result;
            }
        }

        // No patterns match
        return null;
    }
}
