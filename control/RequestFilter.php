<?php

/**
 * A request filter is an object that's executed before and after the request is passed through the controller.
 * "preRequest" filter can short-circuit by returning false, causing a bypass of all controllers and execution
 * of all already-executed filters in reverse order via the "postShorted" method.
 *
 * "postRequest" nor "shorted" filters cannot terminate the execution, unless they throw an exception, which at this
 * point is fatal.
 *
 * If a filter is shorting, it must rewrite the mutable $earlyRes parameter to provide a response. "postShorted"
 * handlers will be called, but the "postRequest" handlers won't.
 *
 * @package framework
 * @subpackage control
 */
interface RequestFilter {
	
	/**
	 * Filter executed before a request processes.
	 *
	 * @param SS_HTTPRequest $request Request container object
	 * @param Session $session Request session
	 * @param DataModel $model Current DataModel
	 * @return null|SS_HTTPResponse Return a response object to short-circuit. Null to continue.
	 */
	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model);

	/**
	 * Filter executed after a shorted request.
	 *
	 * @param SS_HTTPRequest $request Request container object
	 * @param SS_HTTPResponse &$earlyResponse Response output object that resulted from shorted inward pipeline
	 * @param Session $session Request session
	 * @param DataModel $model Current DataModel
	 */
	public function postShorted(
		SS_HTTPRequest $request,
		SS_HTTPResponse &$earlyResponse,
		Session $session,
		DataModel $model
	);

	/**
	 * Filter executed after a successful request. This is not executed during a short-circuit.
	 *
	 * @param SS_HTTPRequest $request Request container object
	 * @param SS_HTTPResponse &$response Response output object
	 * @param Session $session Request session
	 * @param DataModel $model Current DataModel
	 */
	public function postRequest(
		SS_HTTPRequest $request,
		SS_HTTPResponse &$response,
		Session $session,
		DataModel $model
	);
}
