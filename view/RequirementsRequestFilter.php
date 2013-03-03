<?php
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
		SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model
	) {
		// Attach appropriate X-Include-JavaScript and X-Include-CSS headers.
		if($request->isAjax()) {
			Requirements::include_in_response($response);
		}
	}


}
