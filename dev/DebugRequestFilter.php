<?php
/**
 * Outputs an error message if an error response has no body.
 *
 * @package framework
 * @subpackage dev
 */
class DebugRequestFilter implements PostRequestFilter {

	/**
	 * {@inheritDoc}
	 */
	public function postRequest(
		SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model
	) {
		// If we're in live mode and an error is generated without a body, then
		// output a nicer error.
		if(Director::isLive() && $response->isError() && !$response->getBody()) {
			Debug::friendlyError($response->getStatusCode(), $response->getStatusDescription());
		}
	}


}
