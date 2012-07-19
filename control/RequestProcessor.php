<?php

/**
 * Description of RequestProcessor
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class RequestProcessor {

	private $filters = array();

	public function __construct($filters = array()) {
		$this->filters = $filters;
	}

	public function setFilters($filters) {
		$this->filters = $filters;
	}

	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {
		foreach ($this->filters as $filter) {
			$res = $filter->preRequest($request, $session, $model);
			if ($res === false) {
				return false;
			}
		}
	}

	/**
	 * Filter executed AFTER a request
	 */
	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {
		foreach ($this->filters as $filter) {
			$res = $filter->postRequest($request, $response, $model);
			if ($res === false) {
				return false;
			}
		}
	}
}