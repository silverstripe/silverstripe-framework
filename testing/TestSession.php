<?php

/**
 * Represents a test usage session of a web-app
 * It will maintain session-state from request to request
 */
class TestSession {
	private $session;
	private $lastResponse;

	function __construct() {
		$this->session = new Session(array());
	}
	
	/**
	 * Submit a get request
	 */
	function get($url) {
		$this->lastResponse = Director::test($url, null, $this->session);
		return $this->lastResponse;
	}

	/**
	 * Submit a post request
	 */
	function post($url, $data) {
		$this->lastResponse = Director::test($url, $data, $this->session);
		return $this->lastResponse;
	}
	
	/**
	 * Submit the form with the given HTML ID, filling it out with the given data.
	 * Acts on the most recent response
	 */
	function submitForm($formID, $button = null, $data = array()) {
		$page = $this->lastPage();
		$form = $page->getFormById($formID);

		foreach($data as $k => $v) {
			$form->setField(new SimpleByName($k), $v);
		}

		if($button) $submission = $form->submitButton(new SimpleByName($button));
		else $submission = $form->submit();

		$url = Director::makeRelative($form->getAction()->asString());
		
		$postVars = array();
		parse_str($submission->_encode(), $postVars);
		Debug::show($postVars);
		return $this->post($url, $postVars);
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
	 * Get the most recent response, as an HTTPResponse object
	 */
	function lastResponse() {
		return $this->lastResponse;
	}
	
	/**
	 * Get the most recent response's content
	 */
	function lastContent() {
		return $this->lastResponse->getBody();
	}
	
	/**
	 * Get the last response as a SimplePage object
	 */
	function lastPage() {
		require_once("testing/simpletest/http.php");
		require_once("testing/simpletest/page.php");
		require_once("testing/simpletest/form.php");

		$builder = &new SimplePageBuilder();
		$page = &$builder->parse(new TestSession_STResponseWrapper($this->lastResponse));
		$builder->free();
		unset($builder);
		
		return $page;
	}
	
	/**
	 * Get the current session, as a Session object
	 */
	function session() {
		return $this->session;
	}
}

/**
 * Wrapper around HTTPResponse to make it look like a SimpleHTTPResposne
 */
class TestSession_STResponseWrapper {
	private $response;

	function __construct(HTTPResponse $response) {
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