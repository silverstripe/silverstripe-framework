<?php

require_once(dirname(dirname(dirname(__FILE__))).'/view/TemplateGlobalProvider.php');
require_once(dirname(dirname(dirname(__FILE__))).'/control/Director.php');

/**
 * A chain of confirmation tokens to be validated on each request. This allows the application to
 * check multiple tokens at once without having to potentially redirect the user for each of them
 *
 * @internal This class is designed specifically for use pre-startup and may change without warning
 */
class ConfirmationTokenChain
{
	/**
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * @param AbstractConfirmationToken $token
	 */
	public function pushToken(AbstractConfirmationToken $token) {
		$this->tokens[] = $token;
	}

	/**
	 * Collect all tokens that require a redirect
	 *
	 * @return array
	 */
	protected function filteredTokens() {
		$result = array();
		foreach ($this->tokens as $token) {
			if ($token->reloadRequired()) {
				$result[] = $token;
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function suppressionRequired() {
		return (count($this->filteredTokens()) !== 0);
	}

	/**
	 * Suppress URLs & GET vars from tokens that require a redirect
	 */
	public function suppressTokens() {
		foreach ($this->filteredTokens() as $token) {
			$token->suppress();
		}
	}

	/**
	 * @return bool
	 */
	public function reloadRequired() {
		foreach ($this->tokens as $token) {
			if ($token->reloadRequired()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function reloadRequiredIfError() {
		foreach ($this->tokens as $token) {
			if ($token->reloadRequiredIfError()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param bool $includeToken
	 * @return array
	 */
	public function params($includeToken = true) {
		$params = array();
		foreach ($this->tokens as $token) {
			$params = array_merge($params, $token->params($includeToken));
		}

		return $params;
	}

	/**
	 * Fetch the URL we want to redirect to, excluding query string parameters. This may
	 * be the same URL (with a token to be added outside this method), or to a different
	 * URL if the current one has been suppressed
	 *
	 * @return string
	 */
	public function getRedirectUrlBase() {
		// URLConfirmationTokens may alter the URL to suppress the URL they're protecting,
		// so we need to ensure they're inspected last and therefore take priority
		$tokens = $this->filteredTokens();
		usort($tokens, function ($a, $b) {
			return ($a instanceof URLConfirmationToken) ? 1 : 0;
		});

		$urlBase = Director::baseURL();
		foreach ($tokens as $token) {
			$urlBase = $token->getRedirectUrlBase();
		}

		return $urlBase;
	}

	/**
	 * Collate GET vars from all token providers that need to apply a token
	 *
	 * @return array
	 */
	public function getRedirectUrlParams() {
		$params = $_GET;
		unset($params['url']);
		foreach ($this->filteredTokens() as $token) {
			$params = array_merge($params, $token->params());
		}

		return $params;
	}

	/**
	 * @return string
	 */
	protected function redirectURL() {
		$params = http_build_query($this->getRedirectUrlParams());
		return $this->getRedirectUrlBase() . '?' . $params;
	}

	/**
	 * Forces a reload of the request with the applicable tokens included
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
}
