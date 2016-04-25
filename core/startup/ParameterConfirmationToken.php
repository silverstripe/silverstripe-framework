<?php

/**
 * Class ParameterConfirmationToken
 *
 * When you need to use a dangerous GET parameter that needs to be set before core/Core.php is
 * established, this class takes care of allowing some other code of confirming the parameter,
 * by generating a one-time-use token & redirecting with that token included in the redirected URL
 *
 * WARNING: This class is experimental and designed specifically for use pre-startup in main.php
 * It will likely be heavily refactored before the release of 3.2
 *
 * @package framework
 * @subpackage misc
 */
class ParameterConfirmationToken {

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
	 * The validated and checked token for this parameter
	 *
	 * @var string|null A string value, or null if either not provided or invalid
	 */
	protected $token = null;

	protected function pathForToken($token) {
		return TEMP_FOLDER.'/token_'.preg_replace('/[^a-z0-9]+/', '', $token);
	}

	/**
	 * Generate a new random token and store it
	 *
	 * @return string Token name
	 */
	protected function genToken() {
		// Generate a new random token (as random as possible)
		require_once(dirname(dirname(dirname(__FILE__))).'/security/RandomGenerator.php');
		$rg = new RandomGenerator();
		$token = $rg->randomToken('md5');

		// Store a file in the session save path (safer than /tmp, as open_basedir might limit that)
		file_put_contents($this->pathForToken($token), $token);

		return $token;
	}

	/**
	 * Validate a token
	 *
	 * @param string $token
	 * @return boolean True if the token is valid
	 */
	protected function checkToken($token) {
		if(!$token) {
			return false;
		}

		$file = $this->pathForToken($token);
		$content = null;

		if (file_exists($file)) {
			$content = file_get_contents($file);
			unlink($file);
		}

		return $content == $token;
	}

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

	/**
	 * Is the parameter requested?
	 * ?parameter and ?parameter=1 are both considered requested
	 *
	 * @return bool
	 */
	public function parameterProvided() {
		return $this->parameter !== null;
	}

	/**
	 * Is the necessary token provided for this parameter?
	 * A value must be provided for the token
	 *
	 * @return bool
	 */
	public function tokenProvided() {
		return !empty($this->token);
	}

	/**
	 * Is this parameter requested without a valid token?
	 *
	 * @return bool True if the parameter is given without a valid token
	 */
	public function reloadRequired() {
		return $this->parameterProvided() && !$this->tokenProvided();
	}

	/**
	 * Suppress the current parameter by unsetting it from $_GET
	 */
	public function suppress() {
		unset($_GET[$this->parameterName]);
	}

	/**
	 * Determine the querystring parameters to include
	 *
	 * @return array List of querystring parameters with name and token parameters
	 */
	public function params() {
		return array(
			$this->parameterName => $this->parameter,
			$this->parameterName.'token' => $this->genToken()
		);
	}

	/** What to use instead of BASE_URL. Must not contain protocol or host. @var string */
	static public $alternateBaseURL = null;

	protected function currentAbsoluteURL() {
		global $url;

		// Are we http or https? Replicates Director::is_https() without its dependencies/
		$proto = 'http';
		// See https://en.wikipedia.org/wiki/List_of_HTTP_header_fields
		// See https://support.microsoft.com/?kbID=307347
		$headerOverride = false;
		if(TRUSTED_PROXY) {
			$headers = (defined('SS_TRUSTED_PROXY_PROTOCOL_HEADER')) ? array(SS_TRUSTED_PROXY_PROTOCOL_HEADER) : null;
			if(!$headers) {
				// Backwards compatible defaults
				$headers = array('HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_PROTOCOL', 'HTTP_FRONT_END_HTTPS');
			}
			foreach($headers as $header) {
				$headerCompareVal = ($header === 'HTTP_FRONT_END_HTTPS' ? 'on' : 'https');
				if(!empty($_SERVER[$header]) && strtolower($_SERVER[$header]) == $headerCompareVal) {
					$headerOverride = true;
					break;
				}
			}
		}

		if($headerOverride) {
			$proto = 'https';
		} else if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) {
			$proto = 'https';
		} else if(isset($_SERVER['SSL'])) {
			$proto = 'https';
		}

		$parts = array_filter(array(
			// What's our host
			$_SERVER['HTTP_HOST'],
			// SilverStripe base
			self::$alternateBaseURL !== null ? self::$alternateBaseURL : BASE_URL,
			// And URL including base script (eg: if it's index.php/page/url/)
			(defined('BASE_SCRIPT_URL') ? '/' . BASE_SCRIPT_URL : '') . $url,
		));

		// Join together with protocol into our current absolute URL, avoiding duplicated "/" characters
		return "$proto://" . preg_replace('#/{2,}#', '/', implode('/', $parts));
	}

	/**
	 * Forces a reload of the request with the token included
	 * This method will terminate the script with `die`
	 */
	public function reloadWithToken() {
		$location = $this->currentAbsoluteURL();

		// What's our GET params (ensuring they include the original parameter + a new token)
		$params = array_merge($_GET, $this->params());
		unset($params['url']);

		if ($params) $location .= '?'.http_build_query($params);

		// And redirect
		if (headers_sent()) {
			echo "
<script>location.href='$location';</script>
<noscript><meta http-equiv='refresh' content='0; url=$location'></noscript>
You are being redirected. If you are not redirected soon, <a href='$location'>click here to continue the flush</a>
";
		}
		else header('location: '.$location, true, 302);
		die;
	}

	/**
	 * Given a list of token names, suppress all tokens that have not been validated, and
	 * return the non-validated token with the highest priority
	 *
	 * @param array $keys List of token keys in ascending priority (low to high)
	 * @return ParameterConfirmationToken The token container for the unvalidated $key given with the highest priority
	 */
	public static function prepare_tokens($keys) {
		$target = null;
		foreach($keys as $key) {
			$token = new ParameterConfirmationToken($key);
			// Validate this token
			if($token->reloadRequired()) {
				$token->suppress();
				$target = $token;
			}
		}
		return $target;
	}
}
