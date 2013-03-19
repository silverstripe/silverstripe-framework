<?php
/**
 * Handles registering and executing pre- and post-request filters, which allow
 * hooking into the overall request process.
 *
 * @package framework
 * @subpackage control
 * @see PreRequestFilter
 * @see PostRequestFilter
 */
class RequestProcessor {

	private $filters = array();

	/**
	 * @param object[] $filters the filter objects
	 */
	public function __construct(array $filters = array()) {
		$this->filters = $filters;
	}

	/**
	 * Returns the registered filter objects.
	 *
	 * @return object[]
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * Sets the list of filter objects.
	 *
	 * @param object[] $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
	}

	/**
	 * Executes all the registered pre-request filters.
	 *
	 * If any of the filters return FALSE, then method stops further executing
	 * and returns FALSE.
	 *
	 * @param SS_HTTPRequest $request
	 * @param Session $session
	 * @param DataModel $model
	 * @return bool
	 */
	public function preRequest(
		SS_HTTPRequest $request, Session $session, DataModel $model
	) {
		foreach($this->filters as $filter) {
			if($filter instanceof PreRequestFilter) {
				if($filter->preRequest($request, $session, $model) === false) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Executes all the registered post-request filters.
	 *
	 * If any of the filters return FALSE, the method stops executing further
	 * filters and returns FALSE.
	 *
	 * @param SS_HTTPRequest $request
	 * @param SS_HTTPResponse $response
	 * @param DataModel $model
	 * @return bool
	 */
	public function postRequest(
		SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model
	) {
		foreach($this->filters as $filter) {
			if($filter instanceof PostRequestFilter) {
				if($filter->postRequest($request, $response, $model) === false) {
					return false;
				}
			}
		}

		return true;
	}

}