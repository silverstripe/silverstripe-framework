<?php

/**
 * Class HTTPCacheControl
 *
 * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
 */
class HTTPCacheControl extends SS_Object {

	/**
	 * @var static
	 */
	private static $inst;

	/**
	 * Store for all the current directives and their values
	 * Starts with an implicit config for disabled caching
	 *
	 * @var array
	 */
	private $state = array();

	/**
	 * Forcing level of previous setting; higher number wins
	 * Combination of consts belo
	 *w
	 * @var int
	 */
	protected $forcingLevel = 0;

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
	private static $allowed_directives = array(
		'public',
		'private',
		'no-cache',
		'max-age',
		's-maxage',
		'must-revalidate',
		'proxy-revalidate',
		'no-store',
		'no-transform',
	);

	public function __construct()
	{
		parent::__construct();

		// If we've not been provided an initial state, then grab HTTP.cache_contrpl from config
		if (!$this->state) {
			$this->setDirectivesFromArray(Config::inst()->get('HTTP', 'cache_control'));
		}
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
		$forcingLevel = $level + ($force ? self::LEVEL_FORCED : 0);
		if ($forcingLevel < $this->forcingLevel) {
			return false;
		}
		$this->forcingLevel = $forcingLevel;
		return true;
	}

	/**
	 * Low level method for setting directives include any experimental or custom ones added via config
	 *
	 * @param string $directive
	 * @param string|bool $value
	 *
	 * @return $this
	 */
	public function setDirective($directive, $value = null)
	{
		// make sure the directive is in the list of allowed directives
		$allowedDirectives = $this->config()->get('allowed_directives');
		$directive = strtolower($directive);
		if (in_array($directive, $allowedDirectives)) {
			$this->state[$directive] = $value;
		} else {
			throw new InvalidArgumentException('Directive ' . $directive . ' is not allowed');
		}
		return $this;
	}

	/**
	 * Low level method to set directives from an associative array
	 *
	 * @param array $directives
	 *
	 * @return $this
	 */
	public function setDirectivesFromArray($directives)
	{
		foreach ($directives as $directive => $value) {
			// null values mean remove
			if (is_null($value)) {
				$this->removeDirective($directive);
			} else {
				// for legacy reasons we accept the string literal "true" as a bool
				// a bool value of true means there is no explicit value for the directive
				if ($value && (is_bool($value) || strtolower($value) === 'true')) {
					$value = null;
				}
				$this->setDirective($directive, $value);
			}
		}
		return $this;
	}

	/**
	 * Low level method for removing directives
	 *
	 * @param string $directive
	 *
	 * @return $this
	 */
	public function removeDirective($directive)
	{
		unset($this->state[strtolower($directive)]);
		return $this;
	}

	/**
	 * Low level method to check if a directive is currently set
	 *
	 * @param string $directive
	 *
	 * @return bool
	 */
	public function hasDirective($directive)
	{
		return array_key_exists(strtolower($directive), $this->state);
	}

	/**
	 * Low level method to get the value of a directive
	 *
	 * Note that `null` value is acceptable for a directive
	 *
	 * @param string $directive
	 *
	 * @return string|false|null
	 */
	public function getDirective($directive)
	{
		if ($this->hasDirective($directive)) {
			return $this->state[strtolower($directive)];
		}
		return false;
	}

	/**
	 * The cache should not store anything about the client request or server response.
	 *
	 * Set the no-store directive (also removes max-age and s-maxage for consistency purposes)
	 *
	 * @param bool $noStore
	 *
	 * @return $this
	 */
	public function setNoStore($noStore = true)
	{
		if ($noStore) {
			$this->setDirective('no-store');
			$this->removeDirective('max-age');
			$this->removeDirective('s-maxage');
		} else {
			$this->removeDirective('no-store');
		}
		return $this;
	}

	/**
	 * Forces caches to submit the request to the origin server for validation before releasing a cached copy.
	 *
	 * @param bool $noCache
	 *
	 * @return $this
	 */
	public function setNoCache($noCache = true)
	{
		if ($noCache) {
			$this->setDirective('no-cache');
		} else {
			$this->removeDirective('no-cache');
		}
		return $this;
	}

	/**
	 * Specifies the maximum amount of time (seconds) a resource will be considered fresh.
	 * This directive is relative to the time of the request.
	 *
	 * @param int $age
	 *
	 * @return $this
	 */
	public function setMaxAge($age)
	{
		$this->setDirective('max-age', $age);
		return $this;
	}

	/**
	 * Overrides max-age or the Expires header, but it only applies to shared caches (e.g., proxies)
	 * and is ignored by a private cache.
	 *
	 * @param int $age
	 *
	 * @return $this
	 */
	public function setSharedMaxAge($age)
	{
		$this->setDirective('s-maxage', $age);
		return $this;
	}

	/**
	 * The cache must verify the status of the stale resources before using it and expired ones should not be used.
	 *
	 * @param bool $mustRevalidate
	 *
	 * @return $this
	 */
	public function setMustRevalidate($mustRevalidate = true)
	{
		if ($mustRevalidate) {
			$this->setDirective('must-revalidate');
		} else {
			$this->removeDirective('must-revalidate');
		}
		return $this;
	}

	/**
	 * Simple way to set cache control header to a cacheable state.
	 * Use this method over `publicCache()` if you are unsure about caching details.
	 *
	 * Removes `no-store` and `no-cache` directives; other directives will remain in place.
	 * Use alongside `setMaxAge()` to indicate caching.
	 *
	 * Does not set `public` directive. Usually, `setMaxAge()` is sufficient. Use `publicCache()` if this is explicitly required.
	 * See https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching#public_vs_private
	 *
	 * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
	 * @param bool $force Force the cache to public even if its unforced private or public
	 * @return $this
	 */
	public function enableCache($force = false)
	{
		// Only execute this if its forcing level is high enough
		if (!$this->applyChangeLevel(self::LEVEL_ENABLED, $force)) {
			SS_Log::log("Call to enableCache($force) didn't execute as it's lower priority than a previous call", SS_Log::DEBUG);
			return $this;
		}

		$this->removeDirective('no-store');
		$this->removeDirective('no-cache');
		return $this;
	}

	/**
	 * Simple way to set cache control header to a non-cacheable state.
	 * Use this method over `privateCache()` if you are unsure about caching details.
	 * Takes precendence over unforced `enableCache()`, `privateCache()` or `publicCache()` calls.
	 *
	 * Removes all state and replaces it with `no-cache, no-store, must-revalidate`. Although `no-store` is sufficient
	 * the others are added under recommendation from Mozilla (https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#Examples)
	 *
	 * Does not set `private` directive, use `privateCache()` if this is explicitly required.
	 * See https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching#public_vs_private
	 *
	 * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
	 * @param bool $force Force the cache to diabled even if it's forced private or public
	 * @return $this
	 */
	public function disableCache($force = false)
	{
		// Only execute this if its forcing level is high enough
		if (!$this->applyChangeLevel(self::LEVEL_DISABLED, $force )) {
			SS_Log::log("Call to disableCache($force) didn't execute as it's lower priority than a previous call", SS_Log::DEBUG);
			return $this;
		}

		$this->state = array(
			'no-cache' => null,
			'no-store' => null,
			'must-revalidate' => null,
		);
		return $this;
	}

	/**
	 * Advanced way to set cache control header to a non-cacheable state.
	 * Indicates that the response is intended for a single user and must not be stored by a shared cache.
	 * A private cache (e.g. Web Browser) may store the response. Also removes `public` as this is a contradictory directive.
	 *
	 * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
	 * @param bool $force Force the cache to private even if it's forced public
	 * @return $this
	 */
	public function privateCache($force = false)
	{
		// Only execute this if its forcing level is high enough
		if (!$this->applyChangeLevel(self::LEVEL_PRIVATE, $force)) {
			SS_Log::log("Call to privateCache($force) didn't execute as it's lower priority than a previous call", SS_Log::DEBUG);
			return $this;
		}

		// Update the directives
		$this->setDirective('private');
		$this->removeDirective('public');
		$this->removeDirective('no-cache');
		$this->removeDirective('no-store');
		return $this;
	}

	/**
 	 * Advanced way to set cache control header to a cacheable state.
	 * Indicates that the response may be cached by any cache. (eg: CDNs, Proxies, Web browsers)
	 * Also removes `private` as this is a contradictory directive
	 *
	 * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
	 * @param bool $force Force the cache to public even if it's private, unless it's been forced private
	 * @return $this
	 */
	public function publicCache($force = false)
	{
		// Only execute this if its forcing level is high enough
		if (!$this->applyChangeLevel(self::LEVEL_PUBLIC, $force)) {
			SS_Log::log("Call to publicCache($force) didn't execute as it's lower priority than a previous call", SS_Log::DEBUG);
			return $this;
		}

		$this->setDirective('public');
		$this->removeDirective('private');
		$this->removeDirective('no-cache');
		$this->removeDirective('no-store');
		return $this;
	}

	/**
	 * Generate and add the `Cache-Control` header to a response object
	 *
	 * @param SS_HTTPResponse $response
	 *
	 * @return $this
	 */
	public function applyToResponse($response)
	{
		$headers = $this->generateHeaders();
		foreach ($headers as $name => $value) {
			$response->addHeader($name, $value);
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
		$cacheControl = array();
		foreach ($this->state as $directive => $value) {
			if (is_null($value)) {
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
	 * @return array
	 */
	public function generateHeaders()
	{
		return array(
			'Cache-Control' => $this->generateCacheHeader(),
		);
	}

	/**
	 * Reset registered http cache control and force a fresh instance to be built
	 */
	public static function reset() {
		Injector::inst()->unregisterNamedObject(__CLASS__);
	}
}
