<?php

use SilverStripe\Framework\Http\Request;
use SilverStripe\Framework\Http\Response;

/**
 * Includes requirements headers in the response.
 *
 * @package framework
 * @subpackage view
 */
class RequirementsRequestFilter implements PostRequestFilter {

	/**
	 * {@inheritDoc}
	 */
	public function postRequest(
		Request $request, Response $response, DataModel $model
	) {
		// Attach appropriate X-Include-JavaScript and X-Include-CSS headers.
		if($request->isAjax()) {
			Requirements::include_in_response($response);
		}
	}


}
