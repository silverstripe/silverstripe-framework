<?php

namespace SilverStripe\Control\Middleware;

use InvalidArgumentException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Resettable;
use SilverStripe\ORM\FieldType\DBDatetime;

class HTTPCacheControlMiddleware implements HTTPMiddleware, Resettable
{
    use Configurable;
    use Injectable;

    const STATE_ENABLED = 'enabled';

    const STATE_PUBLIC = 'public';

    const STATE_PRIVATE = 'private';

    const STATE_DISABLED = 'disabled';

    /**
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        try {
            $response = $delegate($request);
        } catch (HTTPResponse_Exception $ex) {
            $response = $ex->getResponse();
        }
        if (!$response) {
            return null;
        }

        // Update state based on current request and response objects
        $this->augmentState($request, $response);

        // Add all headers to this response object
        $this->applyToResponse($response);

        if (isset($ex)) {
            throw $ex;
        }
        return $response;
    }

    /**
     * List of states, each of which contains a key of standard directives.
     * Each directive should either be a numeric value, true to enable,
     * or (bool)false or null to disable.
     * Top level key states include `disabled`, `private`, `public`, `enabled`
     * in descending order of precedence.
     *
     * This allows directives to be set independently for individual states.
     *
     * @var array
     */
    protected $stateDirectives = [
        HTTPCacheControlMiddleware::STATE_DISABLED => [
            'no-cache' => true,
            'no-store' => true,
            'must-revalidate' => true,
        ],
        HTTPCacheControlMiddleware::STATE_PRIVATE => [
            'private' => true,
            'must-revalidate' => true,
        ],
        HTTPCacheControlMiddleware::STATE_PUBLIC => [
            'public' => true,
            'must-revalidate' => true,
        ],
        HTTPCacheControlMiddleware::STATE_ENABLED => [
            'no-cache' => true,
            'must-revalidate' => true,
        ]
    ];

    /**
     * Set default state
     *
     * @config
     * @var string
     */
    private static $defaultState = HTTPCacheControlMiddleware::STATE_ENABLED;

    /**
     * Current state
     *
     * @var string
     */
    protected $state = null;

    /**
     * Forcing level of previous setting; higher number wins
     * Combination of consts below
     *
     * @var int
     */
    protected $forcingLevel = null;

    /**
     * List of vary keys
     *
     * @var array|null
     */
    protected $vary = null;

    /**
     * Latest modification date for this response
     *
     * @var int
     */
    protected $modificationDate;

    /**
     * Default vary
     *
     * @var array
     */
    private static $defaultVary = [
        "X-Forwarded-Protocol" => true,
    ];

    /**
     * Default forcing level
     *
     * @config
     * @var int
     */
    private static $defaultForcingLevel = 0;

    /**
     * Forcing level forced, optionally combined with one of the below.
     */
    const LEVEL_FORCED = 10;

    /**
     * Forcing level caching disabled. Overrides public/private.
     */
    const LEVEL_DISABLED = 3;

    /**
     * Forcing level private-cached. Overrides public.
     */
    const LEVEL_PRIVATE = 2;

    /**
     * Forcing level public cached. Lowest priority.
     */
    const LEVEL_PUBLIC = 1;

    /**
     * Forcing level caching enabled.
     */
    const LEVEL_ENABLED = 0;

    /**
     * A list of allowed cache directives for HTTPResponses
     *
     * This doesn't include any experimental directives,
     * use the config system to add to these if you want to enable them
     *
     * @config
     * @var array
     */
    private static $allowed_directives = [
        'public',
        'private',
        'no-cache',
        'max-age',
        's-maxage',
        'must-revalidate',
        'proxy-revalidate',
        'no-store',
        'no-transform',
    ];

    /**
     * Get current vary keys
     *
     * @return array
     */
    public function getVary()
    {
        // Explicitly set vary
        if (isset($this->vary)) {
            return $this->vary;
        }

        // Load default from config
        $defaultVary = $this->config()->get('defaultVary');
        return array_keys(array_filter($defaultVary ?? []));
    }

    /**
     * Add a vary
     *
     * @param string|array $vary
     * @return $this
     */
    public function addVary($vary)
    {
        $combied = $this->combineVary($this->getVary(), $vary);
        $this->setVary($combied);
        return $this;
    }

    /**
     * Set vary
     *
     * @param array|string $vary
     * @return $this
     */
    public function setVary($vary)
    {
        $this->vary = $this->combineVary($vary);
        return $this;
    }

    /**
     * Combine vary strings/arrays into a single array, or normalise a single vary
     *
     * @param string|array[] $varies Each vary as a separate arg
     * @return array
     */
    protected function combineVary(...$varies)
    {
        $merged = [];
        foreach ($varies as $vary) {
            if ($vary && is_string($vary)) {
                $vary = array_filter(preg_split("/\s*,\s*/", trim($vary ?? '')) ?? []);
            }
            if ($vary && is_array($vary)) {
                $merged = array_merge($merged, $vary);
            }
        }
        return array_unique($merged ?? []);
    }


    /**
     * Register a modification date. Used to calculate the "Last-Modified" HTTP header.
     * Can be called multiple times, and will automatically retain the most recent date.
     *
     * @param string|int $date Date string or timestamp
     * @return HTTPCacheControlMiddleware
     */
    public function registerModificationDate($date)
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date ?? '');
        if ($timestamp > $this->modificationDate) {
            $this->modificationDate = $timestamp;
        }
        return $this;
    }

    /**
     * Set current state. Should only be invoked internally after processing precedence rules.
     *
     * @param string $state
     * @return $this
     */
    protected function setState($state)
    {
        if (!array_key_exists($state, $this->stateDirectives ?? [])) {
            throw new InvalidArgumentException("Invalid state {$state}");
        }
        $this->state = $state;
        return $this;
    }

    /**
     * Get current state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state ?: $this->config()->get('defaultState');
    }

    /**
     * Instruct the cache to apply a change with a given level, optionally
     * modifying it with a force flag to increase priority of this action.
     *
     * If the apply level was successful, the change is made and the internal level
     * threshold is incremented.
     *
     * @param int $level Priority of the given change
     * @param bool $force If usercode has requested this action is forced to a higher priority.
     * Note: Even if $force is set to true, other higher-priority forced changes can still
     * cause a change to be rejected if it is below the required threshold.
     * @return bool True if the given change is accepted, and that the internal
     * level threshold is updated (if necessary) to the new minimum level.
     */
    protected function applyChangeLevel($level, $force)
    {
        $forcingLevel = $level + ($force ? HTTPCacheControlMiddleware::LEVEL_FORCED : 0);
        if ($forcingLevel < $this->getForcingLevel()) {
            return false;
        }
        $this->forcingLevel = $forcingLevel;
        return true;
    }

    /**
     * Low level method for setting directives include any experimental or custom ones added via config.
     * You need to specify the state (or states) to apply this directive to.
     * Can also remove directives with false
     *
     * @param array|string $states State(s) to apply this directive to
     * @param string $directive
     * @param int|string|bool $value Flag to set for this value. Set to false to remove, or true to set.
     * String or int value assign a specific value.
     * @return $this
     */
    public function setStateDirective($states, $directive, $value = true)
    {
        if ($value === null) {
            throw new InvalidArgumentException("Invalid directive value");
        }
        // make sure the directive is in the list of allowed directives
        $allowedDirectives = $this->config()->get('allowed_directives');
        $directive = strtolower($directive ?? '');
        if (!in_array($directive, $allowedDirectives ?? [])) {
            throw new InvalidArgumentException('Directive ' . $directive . ' is not allowed');
        }
        foreach ((array)$states as $state) {
            if (!array_key_exists($state, $this->stateDirectives ?? [])) {
                throw new InvalidArgumentException("Invalid state {$state}");
            }
            // Set or unset directive
            if ($value === false) {
                unset($this->stateDirectives[$state][$directive]);
            } else {
                $this->stateDirectives[$state][$directive] = $value;
            }
        }
        return $this;
    }

    /**
     * Low level method to set directives from an associative array
     *
     * @param array|string $states State(s) to apply this directive to
     * @param array $directives
     * @return $this
     */
    public function setStateDirectivesFromArray($states, $directives)
    {
        foreach ($directives as $directive => $value) {
            $this->setStateDirective($states, $directive, $value);
        }
        return $this;
    }

    /**
     * Low level method for removing directives
     *
     * @param array|string $states State(s) to remove this directive from
     * @param string $directive
     * @return $this
     */
    public function removeStateDirective($states, $directive)
    {
        $this->setStateDirective($states, $directive, false);
        return $this;
    }

    /**
     * Low level method to check if a directive is currently set
     *
     * @param string $state State(s) to apply this directive to
     * @param string $directive
     * @return bool
     */
    public function hasStateDirective($state, $directive)
    {
        $directive = strtolower($directive ?? '');
        return isset($this->stateDirectives[$state][$directive]);
    }

    /**
     * Check if the current state has the given directive.
     *
     * @param string $directive
     * @return bool
     */
    public function hasDirective($directive)
    {
        return $this->hasStateDirective($this->getState(), $directive);
    }

    /**
     * Low level method to get the value of a directive for a state.
     * Returns false if there is no directive.
     * True means the flag is set, otherwise the value of the directive.
     *
     * @param string $state
     * @param string $directive
     * @return int|string|bool
     */
    public function getStateDirective($state, $directive)
    {
        $directive = strtolower($directive ?? '');
        if (isset($this->stateDirectives[$state][$directive])) {
            return $this->stateDirectives[$state][$directive];
        }
        return false;
    }

    /**
     * Get the value of the given directive for the current state
     *
     * @param string $directive
     * @return bool|int|string
     */
    public function getDirective($directive)
    {
        return $this->getStateDirective($this->getState(), $directive);
    }

    /**
     * Get directives for the given state
     *
     * @param string $state
     * @return array
     */
    public function getStateDirectives($state)
    {
        return $this->stateDirectives[$state];
    }

    /**
     * Get all directives for the currently active state
     *
     * @return array
     */
    public function getDirectives()
    {
        return $this->getStateDirectives($this->getState());
    }

    /**
     * The cache should not store anything about the client request or server response.
     * Affects all non-disabled states. Use setStateDirective() instead to set for a single state.
     * Set the no-store directive (also removes max-age and s-maxage for consistency purposes)
     *
     * @param bool $noStore
     *
     * @return $this
     */
    public function setNoStore($noStore = true)
    {
        // Affect all non-disabled states
        $applyTo = [HTTPCacheControlMiddleware::STATE_ENABLED, HTTPCacheControlMiddleware::STATE_PRIVATE, HTTPCacheControlMiddleware::STATE_PUBLIC];
        if ($noStore) {
            $this->setStateDirective($applyTo, 'no-store');
            $this->removeStateDirective($applyTo, 'max-age');
            $this->removeStateDirective($applyTo, 's-maxage');
        } else {
            $this->removeStateDirective($applyTo, 'no-store');
        }
        return $this;
    }

    /**
     * Forces caches to submit the request to the origin server for validation before releasing a cached copy.
     * Affects all non-disabled states. Use setStateDirective() instead to set for a single state.
     *
     * @param bool $noCache
     * @return $this
     */
    public function setNoCache($noCache = true)
    {
        // Affect all non-disabled states
        $applyTo = [HTTPCacheControlMiddleware::STATE_ENABLED, HTTPCacheControlMiddleware::STATE_PRIVATE, HTTPCacheControlMiddleware::STATE_PUBLIC];
        if ($noCache) {
            $this->setStateDirective($applyTo, 'no-cache');
            $this->removeStateDirective($applyTo, 'max-age');
            $this->removeStateDirective($applyTo, 's-maxage');
        } else {
            $this->removeStateDirective($applyTo, 'no-cache');
        }
        return $this;
    }

    /**
     * Specifies the maximum amount of time (seconds) a resource will be considered fresh.
     * This directive is relative to the time of the request.
     * Affects all non-disabled states. Use enableCache(), publicCache() or
     * setStateDirective() instead to set the max age for a single state.
     *
     * @param int $age
     * @return $this
     */
    public function setMaxAge($age)
    {
        // Affect all non-disabled states
        $applyTo = [HTTPCacheControlMiddleware::STATE_ENABLED, HTTPCacheControlMiddleware::STATE_PRIVATE, HTTPCacheControlMiddleware::STATE_PUBLIC];
        $this->setStateDirective($applyTo, 'max-age', $age);
        if ($age) {
            $this->removeStateDirective($applyTo, 'no-cache');
            $this->removeStateDirective($applyTo, 'no-store');
        }
        return $this;
    }

    /**
     * Overrides max-age or the Expires header, but it only applies to shared caches (e.g., proxies)
     * and is ignored by a private cache.
     * Affects all non-disabled states. Use setStateDirective() instead to set for a single state.
     *
     * @param int $age
     * @return $this
     */
    public function setSharedMaxAge($age)
    {
        // Affect all non-disabled states
        $applyTo = [HTTPCacheControlMiddleware::STATE_ENABLED, HTTPCacheControlMiddleware::STATE_PRIVATE, HTTPCacheControlMiddleware::STATE_PUBLIC];
        $this->setStateDirective($applyTo, 's-maxage', $age);
        if ($age) {
            $this->removeStateDirective($applyTo, 'no-cache');
            $this->removeStateDirective($applyTo, 'no-store');
        }
        return $this;
    }

    /**
     * The cache must verify the status of the stale resources before using it and expired ones should not be used.
     * Affects all non-disabled states. Use setStateDirective() instead to set for a single state.
     *
     * @param bool $mustRevalidate
     * @return $this
     */
    public function setMustRevalidate($mustRevalidate = true)
    {
        $applyTo = [HTTPCacheControlMiddleware::STATE_ENABLED, HTTPCacheControlMiddleware::STATE_PRIVATE, HTTPCacheControlMiddleware::STATE_PUBLIC];
        $this->setStateDirective($applyTo, 'must-revalidate', $mustRevalidate);
        return $this;
    }

    /**
     * Simple way to set cache control header to a cacheable state.
     * Needs either `setMaxAge()` or the `$maxAge` method argument in order to activate caching.
     *
     * The resulting cache-control headers will be chosen from the 'enabled' set of directives.
     *
     * Does not set `public` directive. Usually, `setMaxAge()` is sufficient. Use `publicCache()` if this is explicitly required.
     * See https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching#public_vs_private
     *
     * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
     * @param bool $force Force the cache to public even if its unforced private or public
     * @param int $maxAge Shortcut for `setMaxAge()`, which is required to actually enable the cache.
     * @return $this
     */
    public function enableCache($force = false, $maxAge = null)
    {
        // Only execute this if its forcing level is high enough
        if ($this->applyChangeLevel(HTTPCacheControlMiddleware::LEVEL_ENABLED, $force)) {
            $this->setState(HTTPCacheControlMiddleware::STATE_ENABLED);
        }

        if (!is_null($maxAge)) {
            $this->setMaxAge($maxAge);
        }

        return $this;
    }

    /**
     * Simple way to set cache control header to a non-cacheable state.
     * Use this method over `privateCache()` if you are unsure about caching details.
     * Takes precedence over unforced `enableCache()`, `privateCache()` or `publicCache()` calls.
     *
     * The resulting cache-control headers will be chosen from the 'disabled' set of directives.
     *
     * Removes all state and replaces it with `no-cache, no-store, must-revalidate`. Although `no-store` is sufficient
     * the others are added under recommendation from Mozilla (https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#Examples)
     *
     * Does not set `private` directive, use `privateCache()` if this is explicitly required.
     * See https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching#public_vs_private
     *
     * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
     * @param bool $force Force the cache to disabled even if it's forced private or public
     * @return $this
     */
    public function disableCache($force = false)
    {
        // Only execute this if its forcing level is high enough
        if ($this->applyChangeLevel(HTTPCacheControlMiddleware::LEVEL_DISABLED, $force)) {
            $this->setState(HTTPCacheControlMiddleware::STATE_DISABLED);
        }
        return $this;
    }

    /**
     * Advanced way to set cache control header to a non-cacheable state.
     * Indicates that the response is intended for a single user and must not be stored by a shared cache.
     * A private cache (e.g. Web Browser) may store the response.
     *
     * The resulting cache-control headers will be chosen from the 'private' set of directives.
     *
     * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
     * @param bool $force Force the cache to private even if it's forced public
     * @return $this
     */
    public function privateCache($force = false)
    {
        // Only execute this if its forcing level is high enough
        if ($this->applyChangeLevel(HTTPCacheControlMiddleware::LEVEL_PRIVATE, $force)) {
            $this->setState(HTTPCacheControlMiddleware::STATE_PRIVATE);
        }
        return $this;
    }

    /**
     * Advanced way to set cache control header to a cacheable state.
     * Indicates that the response may be cached by any cache. (eg: CDNs, Proxies, Web browsers).
     * Needs either `setMaxAge()` or the `$maxAge` method argument in order to activate caching.
     *
     * The resulting cache-control headers will be chosen from the 'private' set of directives.
     *
     * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
     * @param bool $force Force the cache to public even if it's private, unless it's been forced private
     * @param int $maxAge Shortcut for `setMaxAge()`, which is required to actually enable the cache.
     * @return $this
     */
    public function publicCache($force = false, $maxAge = null)
    {
        // Only execute this if its forcing level is high enough
        if ($this->applyChangeLevel(HTTPCacheControlMiddleware::LEVEL_PUBLIC, $force)) {
            $this->setState(HTTPCacheControlMiddleware::STATE_PUBLIC);
        }

        if (!is_null($maxAge)) {
            $this->setMaxAge($maxAge);
        }

        return $this;
    }

    /**
     * Generate all headers to add to this object
     *
     * @param HTTPResponse $response
     *
     * @return $this
     */
    public function applyToResponse($response)
    {
        $headers = $this->generateHeadersFor($response);
        foreach ($headers as $name => $value) {
            if (!$response->getHeader($name)) {
                $response->addHeader($name, $value);
            }
        }
        return $this;
    }

    /**
     * Generate the cache header
     *
     * @return string
     */
    protected function generateCacheHeader()
    {
        $cacheControl = [];
        foreach ($this->getDirectives() as $directive => $value) {
            if ($value === true) {
                $cacheControl[] = $directive;
            } else {
                $cacheControl[] = $directive . '=' . $value;
            }
        }
        return implode(', ', $cacheControl);
    }

    /**
     * Generate all headers to output
     *
     * @param HTTPResponse $response
     * @return array
     */
    public function generateHeadersFor(HTTPResponse $response)
    {
        return array_filter([
            'Last-Modified' => $this->generateLastModifiedHeader(),
            'Vary' => $this->generateVaryHeader($response),
            'Cache-Control' => $this->generateCacheHeader(),
            'Expires' => $this->generateExpiresHeader(),
        ]);
    }

    /**
     * Reset registered http cache control and force a fresh instance to be built
     */
    public static function reset()
    {
        Injector::inst()->unregisterNamedObject(__CLASS__);
    }

    /**
     * @return int
     */
    protected function getForcingLevel()
    {
        if (isset($this->forcingLevel)) {
            return $this->forcingLevel;
        }
        return $this->config()->get('defaultForcingLevel');
    }

    /**
     * Generate vary http header
     *
     * @param HTTPResponse $response
     * @return string|null
     */
    protected function generateVaryHeader(HTTPResponse $response)
    {
        // split the current vary header into it's parts and merge it with the config settings
        // to create a list of unique vary values
        $vary = $this->getVary();
        if ($response->getHeader('Vary')) {
            $vary = $this->combineVary($vary, $response->getHeader('Vary'));
        }
        if ($vary) {
            return implode(', ', $vary);
        }
        return null;
    }

    /**
     * Generate Last-Modified header
     *
     * @return string|null
     */
    protected function generateLastModifiedHeader()
    {
        if (!$this->modificationDate) {
            return null;
        }
        return gmdate('D, d M Y H:i:s', $this->modificationDate) . ' GMT';
    }

    /**
     * Generate Expires http header
     *
     * @return null|string
     */
    protected function generateExpiresHeader()
    {
        $maxAge = $this->getDirective('max-age');
        if ($maxAge === false) {
            return null;
        }

        // Add now to max-age to generate expires
        $expires = DBDatetime::now()->getTimestamp() + $maxAge;
        return gmdate('D, d M Y H:i:s', $expires) . ' GMT';
    }

    /**
     * Update state based on current request and response objects
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     */
    protected function augmentState(HTTPRequest $request, HTTPResponse $response)
    {
        // Errors disable cache (unless some errors are cached intentionally by usercode)
        if ($response->isError() || $response->isRedirect()) {
            // Even if publicCache(true) is specified, errors will be uncacheable
            $this->disableCache(true);
        } elseif ($request->getSession()->getAll()) {
            // If sessions exist we assume that the responses should not be cached by CDNs / proxies as we are
            // likely to be supplying information relevant to the current user only

            // Don't force in case user code chooses to opt in to public caching
            $this->privateCache();
        }
    }
}
