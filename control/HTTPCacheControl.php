<?php

/**
 * Class HTTPCacheControl
 *
 *
 */
class HTTPCacheControl extends SS_Object {

	/**
	 * @var static
	 */
	private static $inst;

	/**
	 * Store for all the current directives and their values
	 *
	 * @var array
	 */
	private $state = array();

	/**
	 * Whether the cache-control object is locked to further changes
	 *
	 * @var bool
	 */
	protected $locked = false;

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

	/**
	 * Lock the current state of the cache control to prevent further modifications
	 *
	 * @return $this
	 */
	public function lock()
	{
		$this->locked = true;
		return $this;
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
		if ($this->locked) {
			user_error(
				'Cannot set further directives; cache is locked.' .
				'Use `Injector::inst()->unregisterNamedObject("HTTPCacheControl");`' .
				'to reset the cache control state',
				E_USER_WARNING
			);
			return;
		}
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
		if ($this->locked) {
			user_error(
				'Cannot set further directives; cache is locked.' .
				'Use `Injector::inst()->unregisterNamedObject("HTTPCacheControl");`' .
				'to reset the cache control state',
				E_USER_WARNING
			);
			return;
		}
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
	 * Indicates that the response may be cached by any cache. (eg: CDNs, Proxies, Web browsers)
	 *
	 * Also removes `public` as this is a contradictory directive
	 *
	 * @return $this
	 */
	public function setPublic()
	{
		$this->setDirective('public');
		$this->removeDirective('private');
		return $this;
	}

	/**
	 * Indicates that the response is intended for a single user and must not be stored by a shared cache.
	 * A private cache may store the response.
	 *
	 * Also removes `private` as this is a contradictory directive
	 *
	 * @return $this
	 */
	public function setPrivate()
	{
		$this->setDirective('private');
		$this->removeDirective('public');
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
	 * Helper method to turn the cache control header into a non-cacheable state
	 *
	 * Removes all state and replaces it with `no-cache, no-store, must-revalidate`. Although `no-store` is sufficient
	 * the others are added under recommendation from Mozilla (https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#Examples)
	 *
	 * @return $this
	 */
	public function disableCaching()
	{
		$this->state = array(
			'no-cache' => null,
			'no-store' => null,
			'must-revalidate' => null,
		);
		$this->lock();
		return $this;
	}

	/**
	 * Helper function to set the current cache to private
	 *
	 * @return $this
	 */
	public function privateCache()
	{
		$this->setPrivate();
		return $this;
	}

	/**
	 * Helper function to set the current cache to public
	 *
	 * @param bool $force Force the cache to public even if it's private
	 *
	 * @return $this
	 */
	public function publicCache($force = false)
	{
		if ($force || !$this->hasDirective('private')) {
			$this->setPublic();
		} else {
			user_error('Cannot change a private cache to public', E_USER_WARNING);
		}
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
		$response->addHeader('Cache-Control', $this->generateCacheHeader());
		return $this;
	}

	/**
	 * Generate the cache header
	 *
	 * @return string
	 */
	public function generateCacheHeader()
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

}
