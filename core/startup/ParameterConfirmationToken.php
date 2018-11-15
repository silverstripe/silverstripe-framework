<?php

require_once(dirname(__FILE__).'/AbstractConfirmationToken.php');
require_once(dirname(dirname(dirname(__FILE__))).'/view/TemplateGlobalProvider.php');
require_once(dirname(dirname(dirname(__FILE__))).'/control/Director.php');

/**
 * This is used to protect dangerous GET parameters that need to be detected early in the request
 * lifecycle by generating a one-time-use token & redirecting with that token included in the
 * redirected URL
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 */
class ParameterConfirmationToken extends AbstractConfirmationToken {

	/**
	 * The name of the parameter
	 *
	 * @var string
	 */
	protected $parameterName = null;

	/**
	 * The parameter given
	 *
	 * @var string|null The string value, or null if not provided
	 */
	protected $parameter = null;

	/**
	 * Create a new ParameterConfirmationToken
	 *
	 * @param string $parameterName Name of the querystring parameter to check
	 */
	public function __construct($parameterName) {
		// Store the parameter name
		$this->parameterName = $parameterName;

		// Store the parameter value
		$this->parameter = isset($_GET[$parameterName]) ? $_GET[$parameterName] : null;

		// If the token provided is valid, mark it as such
		$token = isset($_GET[$parameterName.'token']) ? $_GET[$parameterName.'token'] : null;
		if ($this->checkToken($token)) {
			$this->token = $token;
		}
	}

	/**
	 * Get the name of this token
	 *
	 * @return string
	 */
	public function getName() {
		return $this->parameterName;
	}

	public function parameterProvided() {
		return $this->parameter !== null;
	}

	public function reloadRequired() {
		return $this->parameterProvided() && !$this->tokenProvided();
	}

	public function suppress() {
		unset($_GET[$this->parameterName]);
	}

	public function params($includeToken = true) {
		$params = array(
			$this->parameterName => $this->parameter,
		);
		if ($includeToken) {
			$params[$this->parameterName . 'token'] = $this->genToken();
		}
		return $params;
	}

	public function getRedirectUrlBase() {
		return (!$this->parameterProvided()) ? Director::baseURL() : $this->currentAbsoluteURL();
	}

	public function getRedirectUrlParams() {
		$params = (!$this->parameterProvided())
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
