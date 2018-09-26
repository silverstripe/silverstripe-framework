<?php

require_once(dirname(__FILE__).'/AbstractConfirmationToken.php');
require_once(dirname(dirname(dirname(__FILE__))).'/control/Director.php');
require_once(dirname(dirname(dirname(__FILE__))).'/view/TemplateGlobalProvider.php');

/**
 * This is used to protect dangerous URLs that need to be detected early in the request lifecycle
 * by generating a one-time-use token & redirecting with that token included in the redirected URL
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 */
class URLConfirmationToken extends AbstractConfirmationToken {

	/**
	 * @var string
	 */
	protected $urlToCheck;

	/**
	 * @var string
	 */
	protected $currentURL;

	/**
	 * @var string
	 */
	protected $tokenParameterName;

	/**
	 * @param string $urlToCheck URL to check
	 */
	public function __construct($urlToCheck)
	{
		$this->urlToCheck = $urlToCheck;
		global $url;
		// Strip leading/trailing slashes
		$this->currentURL = preg_replace(array('/\/+/','/^\//', '/\/$/'), array('/','',''), $url);
		$this->tokenParameterName = preg_replace('/[^a-z0-9]/i', '', $urlToCheck) . 'token';

		// If the token provided is valid, mark it as such
		$token = isset($_GET[$this->tokenParameterName]) ? $_GET[$this->tokenParameterName] : null;
		if ($this->checkToken($token)) {
			$this->token = $token;
		}
	}

	/**
	 * @return bool
	 */
	protected function urlMatches() {
		return ($this->currentURL === $this->urlToCheck);
	}

	/**
	 * @return string
	 */
	public function getURLToCheck() {
		return $this->urlToCheck;
	}

	public function reloadRequired() {
		return $this->urlMatches() && !$this->tokenProvided();
	}

	public function suppress() {
		$_SERVER['REQUEST_URI'] = '/';
		$_GET['url'] = $_REQUEST['url'] = '/';
	}

	public function params($includeToken = true) {
		$params = array();
		if ($includeToken) {
			$params[$this->tokenParameterName] = $this->genToken();
		}

		return $params;
	}

	public function currentURL() {
		return Director::baseURL() . $this->currentURL;
	}

	public function getRedirectUrlBase() {
		return (!$this->urlMatches()) ? Director::baseURL() : $this->currentURL();
	}

	public function getRedirectUrlParams() {
		$params = (!$this->urlMatches())
			? $this->params()
			: array_merge($_GET, $this->params());

		if (isset($params['url'])) {
			unset($params['url']);
		}

		return $params;
	}

	protected function redirectURL() {
		$query = http_build_query($this->getRedirectUrlParams());
		return $this->getRedirectUrlBase() . '?' . $query;
	}
}
