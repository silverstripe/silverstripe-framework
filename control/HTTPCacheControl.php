<?php

/**
 * Class HTTPCacheControl
 *
 *
 */
class HTTPCacheControl {

	/**
	 * @var static
	 */
	private static $inst;

	private $state = array();

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
	 * @return static
	 */
	public static function inst()
	{
		return static::$inst ?: static::$inst = new static();
	}

	public static function reset()
	{
		static::$inst = null;
	}

	public function setDirective($directive, $value = null)
	{
		$allowedDirectives = Config::inst()->get(__CLASS__, 'allowed_directives');
		$directive = strtolower($directive);
		if (in_array($directive, $allowedDirectives)) {
			$this->state[$directive] = $value;
		} else {
			throw new InvalidArgumentException('Directive ' . $directive . ' is not allowed');
		}
		return $this;
	}

	public function setDirectivesFromArray($directives)
	{
		foreach ($directives as $directive => $value) {
			if (is_null($value)) {
				$this->removeDirective($directive);
			} else {
				if ($value && (is_bool($value) || strtolower($value) === 'true')) {
					$value = null;
				}
				$this->setDirective($directive, $value);
			}
		}
		return $this;
	}

	public function removeDirective($directive)
	{
		unset($this->state[strtolower($directive)]);
		return $this;
	}

	public function hasDirective($directive)
	{
		return array_key_exists(strtolower($directive), $this->state);
	}

	public function getDirective($directive)
	{
		if ($this->hasDirective($directive)) {
			return $this->state[strtolower($directive)];
		}
		return false;
	}

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

	public function setNoCache($noCache = true)
	{
		if ($noCache) {
			$this->setDirective('no-cache');
		} else {
			$this->removeDirective('no-cache');
		}
		return $this;
	}

	public function setPublic()
	{
		$this->setDirective('public');
		$this->removeDirective('private');
		return $this;
	}

	public function setPrivate()
	{
		$this->setDirective('private');
		$this->removeDirective('public');
		return $this;
	}

	public function setMaxAge($age)
	{
		$this->setDirective('max-age', $age);
		return $this;
	}

	public function setSharedMaxAge($age)
	{
		$this->setDirective('s-maxage', $age);
		return $this;
	}

	public function setMustRevalidate($mustRevalidate = true)
	{
		if ($mustRevalidate) {
			$this->setDirective('must-revalidate');
		} else {
			$this->removeDirective('must-revalidate');
		}
		return $this;
	}

	public function disableCaching()
	{
		$this->state = array(
			'no-cache' => null,
			'no-store' => null,
			'must-revalidate' => null,
		);
		return $this;
	}

	public function privateCache()
	{
		$this->setPrivate();
		$this->setMustRevalidate();
		return $this;
	}

	public function publicCache()
	{
		$this->setPublic();
		return $this;
	}

	/**
	 * @param SS_HTTPResponse $response
	 *
	 * @return $this
	 */
	public function applyToResponse($response)
	{
		$response->addHeader('Cache-Control', $this->generateCacheHeader());
		return $this;
	}

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
