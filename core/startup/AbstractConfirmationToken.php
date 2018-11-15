<?php

/**
 * Shared functionality for token-based authentication of potentially dangerous URLs or query
 * string parameters
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 */
abstract class AbstractConfirmationToken {

	/**
	 * The validated and checked token for this parameter
	 *
	 * @var string|null A string value, or null if either not provided or invalid
	 */
	protected $token = null;

	/**
	 * What to use instead of BASE_URL. Must not contain protocol or host.
	 *
	 * @var string
	 */
	public static $alternateBaseURL = null;

	/**
	 * Given a list of token names, suppress all tokens that have not been validated, and
	 * return the non-validated token with the highest priority
	 *
	 * @param array $keys List of token keys in ascending priority (low to high)
	 * @return static The token container for the unvalidated $key given with the highest priority
	 */
	public static function prepare_tokens($keys) {
		$target = null;
		foreach ($keys as $key) {
			$token = new static($key);
			// Validate this token
			if ($token->reloadRequired()) {
				$token->suppress();
				$target = $token;
			}
		}
		return $target;
	}

	/**
	 * Generate a local filesystem path to store a token
	 *
	 * @param $token
	 * @return string
	 */
	protected function pathForToken($token) {
		return TEMP_FOLDER . DIRECTORY_SEPARATOR . 'token_' . preg_replace('/[^a-z0-9]+/', '', $token);
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
	 * Is the necessary token provided for this parameter?
	 * A value must be provided for the token
	 *
	 * @return bool
	 */
	public function tokenProvided() {
		return !empty($this->token);
	}

	/**
	 * Validate a token
	 *
	 * @param string $token
	 * @return boolean True if the token is valid
	 */
	protected function checkToken($token) {
		if (!$token) {
			return false;
		}

		$file = $this->pathForToken($token);
		$content = null;

		if (file_exists($file)) {
			$content = file_get_contents($file);
			unlink($file);
		}

		return $content === $token;
	}

	/**
	 * Get redirect url, excluding querystring
	 *
	 * @return string
	 */
	protected function currentAbsoluteURL() {
		global $url;

		// Preserve BC - this has been moved from ParameterConfirmationToken
		require_once(dirname(__FILE__).'/ParameterConfirmationToken.php');
		if (isset(ParameterConfirmationToken::$alternateBaseURL)) {
			self::$alternateBaseURL = ParameterConfirmationToken::$alternateBaseURL;
		}

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
	 */
	public function reloadWithToken() {
		require_once(dirname(dirname(__FILE__)).'/Convert.php');
		$location = $this->redirectURL();
		$locationJS = Convert::raw2js($location);
		$locationATT = Convert::raw2att($location);

		if (headers_sent()) {
			echo "
<script>location.href='{$locationJS}';</script>
<noscript><meta http-equiv='refresh' content='0; url={$locationATT}'></noscript>
You are being redirected. If you are not redirected soon, <a href='{$locationATT}'>click here to continue</a>
";
		} else {
			header("location: {$location}", true, 302);
		}

		die;
	}

	/**
	 * Is this parameter requested without a valid token?
	 *
	 * @return bool True if the parameter is given without a valid token
	 */
	abstract public function reloadRequired();

	/**
	 * Suppress the current parameter for the duration of this request
	 */
	abstract public function suppress();

	/**
	 * Determine the querystring parameters to include
	 *
	 * @param bool $includeToken Include the token value?
	 * @return array List of querystring parameters, possibly including token parameter
	 */
	abstract public function params($includeToken = true);

	/**
	 * @return string
	 */
	abstract public function getRedirectUrlBase();

	/**
	 * @return array
	 */
	abstract public function getRedirectUrlParams();

	/**
	 * Get redirection URL
	 *
	 * @return string
	 */
	abstract protected function redirectURL();
}
