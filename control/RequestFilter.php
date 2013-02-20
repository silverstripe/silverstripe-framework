<?php

use SilverStripe\Framework\Http\Request;
use SilverStripe\Framework\Http\Response;

/**
 * A request filter is an object that's executed before and after a
 * request occurs. By returning 'false' from the preRequest method,
 * request execution will be stopped from continuing
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
interface RequestFilter {
	/**
	 * Filter executed before a request processes
	 * 
	 * @return boolean (optional)
	 *				Whether to continue processing other filters
	 */
	public function preRequest(Request $request, Session $session, DataModel $model);

	/**
	 * Filter executed AFTER a request
	 */
	public function postRequest(Request $request, Response $response, DataModel $model);
}