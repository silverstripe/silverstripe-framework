<?php
/**
 * Represents a test usage session of a web-app
 * It will maintain session-state from request to request
 * 
 * @package sapphire
 * @subpackage testing
 */
class TestSession {
	private $session;
	private $lastResponse;
	
	/**
	 * @param Controller $controller Necessary to use the mock session
	 * created in {@link session} in the normal controller stack,
	 * e.g. to overwrite Member::currentUser() with custom login data.
	 */
	protected $controller;
	
	/**
	 * @var string $lastUrl Fake HTTP Referer Tracking, set in {@link get()} and {@link post()}.
	 */
	private $lastUrl;

	function __construct() {
		$this->session = new Session(array());
		$this->controller = new Controller();
		$this->controller->setSession($this->session);
		$this->controller->pushCurrent();
	}
	
	function __destruct() {
		// Shift off anything else that's on the stack.  This can happen if something throws
		// an exception that causes a premature TestSession::__destruct() call
		while(Controller::curr() != $this->controller) Controller::curr()->popCurrent();

		$this->controller->popCurrent();
	}
	
	/**
	 * Submit a get request
	 * @uses Director::test()
	 */
	function get($url, $session = null, $headers = null, $cookies = null) {
		$headers = (array) $headers;
		if($this->lastUrl && !isset($headers['Referer'])) $headers['Referer'] = $this->lastUrl;
		$this->lastResponse = Director::test($url, null, $session ? $session : $this->session, null, null, $headers, $cookies);
		$this->lastUrl = $url;
		if(!$this->lastResponse) user_error("Director::test($url) returned null", E_USER_WARNING);
		return $this->lastResponse;
	}

	/**
	 * Submit a post request
	 * @uses Director::test()
	 */
	function post($url, $data, $headers = null, $session = null, $body = null, $cookies = null) {
		$headers = (array) $headers;
		if($this->lastUrl && !isset($headers['Referer'])) $headers['Referer'] = $this->lastUrl;
		$this->lastResponse = Director::test($url, $data, $session ? $session : $this->session, null, $body, $headers, $cookies);
		$this->lastUrl = $url;
		if(!$this->lastResponse) user_error("Director::test($url) returned null", E_USER_WARNING);
		return $this->lastResponse;
	}
	
	/**
	 * Submit the form with the given HTML ID, filling it out with the given data.
	 * Acts on the most recent response
	 */
	function submitForm($formID, $button = null, $data = array()) {
		$page = $this->lastPage();
		if($page) {
			$form = $page->getFormById($formID);
			if (!$form) {
				user_error("TestSession::submitForm failed to find the form {$formID}");
			}

			foreach($data as $k => $v) {
				$form->setField(new SimpleByName($k), $v);
			}

			if($button) $submission = $form->submitButton(new SimpleByName($button));
			else $submission = $form->submit();

			$url = Director::makeRelative($form->getAction()->asString());

			$postVars = array();
			parse_str($submission->_encode(), $postVars);
			return $this->post($url, $postVars);
			
		} else {
			user_error("TestSession::submitForm called when there is no form loaded.  Visit the page with the form first", E_USER_WARNING);
		}
	}
	
	/**
	 * If the last request was a 3xx response, then follow the redirection
	 */
	function followRedirection() {
		if($this->lastResponse->getHeader('Location')) {
			$url = Director::makeRelative($this->lastResponse->getHeader('Location'));
			$url = strtok($url, '#');
			return $this->get($url);
		}
	}
	
	/**
	 * Returns true if the last response was a 3xx redirection
	 */
	function wasRedirected() {
		$code = $this->lastResponse->getStatusCode();
		return $code >= 300 && $code < 400;
	}
	
	/**
	 * Get the most recent response, as an SS_HTTPResponse object
	 */
	function lastResponse() {
		return $this->lastResponse;
	}
	
	/**
	 * Get the most recent response's content
	 */
	function lastContent() {
		if(is_string($this->lastResponse)) return $this->lastResponse;
		else return $this->lastResponse->getBody();
	}
	
	function cssParser() {
		return new CSSContentParser($this->lastContent());
	}

	
	/**
	 * Get the last response as a SimplePage object
	 */
	function lastPage() {
		require_once("thirdparty/simpletest/http.php");
		require_once("thirdparty/simpletest/page.php");
		require_once("thirdparty/simpletest/form.php");

		$builder = new SimplePageBuilder();
		if($this->lastResponse) {
			$page = &$builder->parse(new TestSession_STResponseWrapper($this->lastResponse));
			$builder->free();
			unset($builder);
		
			return $page;
		}
	}
	
	/**
	 * Get the current session, as a Session object
	 */
	function session() {
		return $this->session;
	}
}

/**
 * Wrapper around SS_HTTPResponse to make it look like a SimpleHTTPResposne
 * 
 * @package sapphire
 * @subpackage testing
 */
class TestSession_STResponseWrapper {
	private $response;

	function __construct(SS_HTTPResponse $response) {
		$this->response = $response;
	}
	
	function getContent() {
		return $this->response->getBody();
	}
	
	function getError() {
		return "";
	}
	
	function getSent() {
		return null;
	}
	
	function getHeaders() {
		return "";
	}
	
	function getMethod() {
		return "GET";
	}
	
	function getUrl() {
		return "";
	}
	
	function getRequestData() {
		return null;
	}
}
