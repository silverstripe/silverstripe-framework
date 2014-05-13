<?php

/**
 * Represents a request processer that delegates pre and post request handling to nested request filters
 *
 * @package framework
 * @subpackage control
 */
class RequestProcessor implements RequestFilter {

	/**
	 * List of currently assigned request filters
	 *
	 * @var array
	 */
	private $filters = array();

	public function __construct($filters = array()) {
		$this->filters = $filters;
	}

	/**
	 * Assign a list of request filters
	 *
	 * @param array $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
	}

	/**
	 * Dynamically add a filter with the highest priority.
	 *
	 * @param RequestFilter $filter
	 */
	public function unshiftFilter($filter) {
		array_unshift($this->filters, $filter);
	}

	/**
	 * Dynamically add a filter with the lowest priority.
	 *
	 * @param RequestFilter $filter
	 */
	public function pushFilter($filter) {
		array_push($this->filters, $filter);
	}

	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {
		foreach ($this->filters as $filter) {
			$result = $filter->preRequest($request, $session, $model);
			if ($result!==true) return $result;
		}

		return true;
	}

	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {
		// The highest priority filter should apply last.
		$filters = array_reverse($this->filters);

		foreach ($filters as $filter) {
			$result = $filter->postRequest($request, $response, $model);
			if ($result!==true) return $result;
		}

		return true;
	}
}
