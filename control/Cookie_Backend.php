<?php

/**
 * The Cookie_Backend interface for use with `Cookie::$inst`.
 *
 * See Cookie_DefaultBackend and Cookie
 *
 * @package framework
 * @subpackage misc
 */
interface Cookie_Backend {

	/**
	 * When creating the backend we want to store the existing cookies in our
	 * "existing" array. This allows us to distinguish between cookies we recieved
	 * or we set ourselves (and didn't get from the browser)
	 *
	 * @param array $cookies The existing cookies to load into the cookie jar
	 */
	public function __construct($cookies = array());

	/**
	 * Set a cookie
	 *
	 * @param string $name The name of the cookie
	 * @param string $value The value for the cookie to hold
	 * @param int $expiry The number of days until expiry
	 * @param string $path The path to save the cookie on (falls back to site base)
	 * @param string $domain The domain to make the cookie available on
	 * @param boolean $secure Can the cookie only be sent over SSL?
	 * @param boolean $httpOnly Prevent the cookie being accessible by JS
	 */
	public function set($name, $value, $expiry = 90, $path = null, $domain = null, $secure = false, $httpOnly = true);

	/**
	 * Get the cookie value by name
	 *
	 * @param string $name The name of the cookie to get
	 * @param boolean $includeUnsent Include cookies we've yet to send when fetching values
	 *
	 * @return string|null The cookie value or null if unset
	 */
	public function get($name, $includeUnsent = true);

	/**
	 * Get all the cookies
	 *
	 * @param boolean $includeUnsent Include cookies we've yet to send
	 * @return array All the cookies
	 */
	public function getAll($includeUnsent = true);

	/**
	 * Force the expiry of a cookie by name
	 *
	 * @param string $name The name of the cookie to expire
	 * @param string $path The path to save the cookie on (falls back to site base)
	 * @param string $domain The domain to make the cookie available on
	 * @param boolean $secure Can the cookie only be sent over SSL?
	 * @param boolean $httpOnly Prevent the cookie being accessible by JS
	 */
	public function forceExpiry($name, $path = null, $domain = null, $secure = false, $httpOnly = true);

}
