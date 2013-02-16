<?php

use SilverStripe\Framework\Http\Request;

/**
 * A filter which is run before a request is handled.
 *
 * @package framework
 * @subpackage control
 * @see RequestProcessor
 */
interface PreRequestFilter {

	/**
	 * The main filter method, which is run before a request is handled.
	 *
	 * @param Request $request the incoming request
	 * @param Session $session the session
	 * @param DataModel $model the data model
	 * @return bool if the result is FALSE, a 400 error is thrown
	 */
	public function preRequest(
		Request $request, Session $session, DataModel $model
	);

}
