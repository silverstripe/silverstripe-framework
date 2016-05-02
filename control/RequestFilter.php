<?php

/**
 * A request filter is an object that's executed before and after a
 * request occurs. By returning 'false' from the preRequest method,
 * request execution will be stopped from continuing
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 * @package framework
 * @subpackage control
 */
interface RequestFilter {

	/**
	 * Filter executed before a request processes
	 *
	 * @param SS_HTTPRequest $request Request container object
	 * @param Session $session Request session
	 * @param DataModel $model Current DataModel
	 * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
	 */
	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model);

	/**
	 * Filter executed AFTER a request
	 *
	 * @param SS_HTTPRequest $request Request container object
	 * @param SS_HTTPResponse $response Response output object
	 * @param DataModel $model Current DataModel
	 * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
	 */
	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model);
}
