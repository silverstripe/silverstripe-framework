<?php

/**
 * Description of RequestProcessor
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class RequestProcessor {
	
	private $filters = array();
	
	public function __construct($filters) {
		$this->filters = $filters;
	}
	
	public function preRequest(SS_HTTPRequest $request, Session $session) {
		foreach ($this->filters as $filter) {
			$res = $filter->preRequest($request, $session);
			if ($res === false) {
				return false;
			}
		}
	}

	/**
	 * Filter executed AFTER a request
	 */
	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response) {
		foreach ($this->filters as $filter) {
			$res = $filter->postRequest($request, $response);
			if ($res === false) {
				return false;
			}
		}
	}
}
