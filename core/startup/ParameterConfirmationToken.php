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
 */
class ParameterConfirmationToken {
	protected $parameterName = null;
	protected $parameter = null;
	protected $token = null;

	protected function pathForToken($token) {
		return TEMP_FOLDER.'/token_'.preg_replace('/[^a-z0-9]+/', '', $token);
	}

	protected function genToken() {
		// Generate a new random token (as random as possible)
		require_once(dirname(dirname(dirname(__FILE__))).'/security/RandomGenerator.php');
		$rg = new RandomGenerator();
		$token = $rg->randomToken('md5');

		// Store a file in the session save path (safer than /tmp, as open_basedir might limit that)
		file_put_contents($this->pathForToken($token), $token);

		return $token;
	}

	protected function checkToken($token) {
		$file = $this->pathForToken($token);
		$content = null;

		if (file_exists($file)) {
			$content = file_get_contents($file);
			unlink($file);
		}

		return $content == $token;
	}

	public function __construct($parameterName) {
		// Store the parameter name
		$this->parameterName = $parameterName;
		// Store the parameter value
		$this->parameter = isset($_GET[$parameterName]) ? $_GET[$parameterName] : null;
		// Store the token
		$this->token = isset($_GET[$parameterName.'token']) ? $_GET[$parameterName.'token'] : null;

		// If a token was provided, but isn't valid, ignore it
		if ($this->token && (!$this->checkToken($this->token))) $this->token = null;
	}

	public function parameterProvided() {
		return $this->parameter !== null;
	}

	public function tokenProvided() {
		return $this->token !== null;
	}

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

		// Are we http or https?
		$proto = 'http';

		if(isset($_SERVER['HTTP_X_FORWARDED_PROTOCOL'])) {
			if(strtolower($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) == 'https') $proto = 'https';
		}

		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) $proto = 'https';
		if(isset($_SERVER['SSL'])) $proto = 'https';

		$parts = array_filter(array(
			// What's our host
			$_SERVER['HTTP_HOST'],
			// SilverStripe base
			self::$alternateBaseURL !== null ? self::$alternateBaseURL : BASE_URL,
			// And URL
			$url
		));

		// Join together with protocol into our current absolute URL, avoiding duplicated "/" characters
		return "$proto://" . preg_replace('#/{2,}#', '/', implode('/', $parts));
	}

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
}
