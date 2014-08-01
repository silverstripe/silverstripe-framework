<?php

/**
 * Represents a request processor that delegates pre and post request handling to nested request filters.
 * It also handles short-circuiting of the inward filters.
 *
 * See "Director" chapter in the documentation for further details.
 *
 * @package framework
 * @subpackage control
 */
class RequestProcessor {

	/**
	 * List of currently assigned request filters
	 *
	 * @var array
	 */
	private $filters = array();

	/**
	 * List of the filters that have been successfully executed.
	 */
	private $executedFilters = array();

	/**
	 * Store the filter that has caused the short-circuit.
	 */
	private $shortedFilter = null;

	public function __construct($filters = array()) {
		$this->filters = $filters;
		$this->executedFilters = array();
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
	 * Find out filters.
	 *
	 * @return array of RequestFilters
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * Provide the list of filters which have so far been executed. If the pipeline has shorted, this will contain
	 * all filters up to but excluding the one that caused the short.
	 *
	 * @return array of RequestFilter
	 */
	public function getExecutedFilters() {
		return $this->executedFilters;
	}

	/**
	 * Provide the filter object that has caused the short-circuiting.
	 *
	 * @return RequestFilter
	 */
	public function getShortedFilter() {
		return $this->shortedFilter;
	}

	/**
	 * Apply the inward pipeline.
	 *
	 * @param SS_HTTPRequest $req Request container object
	 * @param Session $session Request session
	 * @param DataModel $model Current DataModel
	 * @return null|SS_HTTPResponse Returns a response object to signify a short-circuit.
	 */
	public function preRequestPipeline(SS_HTTPRequest $req, Session $session, DataModel $model) {
		$earlyResponse = null;

		foreach ($this->filters as $filter) {
			$earlyResponse = $filter->preRequest($req, $session, $model);
			if ($earlyResponse) {
				$this->shortedFilter = $filter;
				break;
			}

			// Store the successful filter in case the pipeline shorts.
			$this->executedFilters[] = $filter;
		}

		// If pipeline is shorting, re-apply the filters that already executed in reverse order.
		if ($earlyResponse) {
			$filters = array_reverse($this->executedFilters);
			foreach ($filters as $filter) {
				$filter->postShorted($req, $earlyResponse, $session, $model);
			}
			return $earlyResponse;
		}

		return null;
	}

	/**
	 * Apply the outward pipeline (for successful requests with real responses).
	 *
	 * @param SS_HTTPRequest $req Request container object
	 * @param SS_HTTPResponse $res Response output object (mutable)
	 * @param Session $session Request session
	 * @param DataModel $model Current DataModel
	 */
	public function postRequestPipeline(
		SS_HTTPRequest $req,
		SS_HTTPResponse &$res,
		Session $session,
		DataModel $model
	) {
		// Apply filters in reverse order so that the outward filter applies last.
		$filters = array_reverse($this->executedFilters);

		foreach ($filters as $filter) {
			$filter->postRequest($req, $res, $session, $model);
		}
	}
}
