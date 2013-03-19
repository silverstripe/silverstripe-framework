<?php
/**
 * A request filter which is run after a request, before it is returned to the
 * client.
 *
 * @package framework
 * @subpackage control
 * @see RequestProcessor
 */
interface PostRequestFilter {

	/**
	 * The main filter method which is called before the response is returned.
	 *
	 * @param SS_HTTPRequest $request the request
	 * @param SS_HTTPResponse $response the generated response
	 * @param DataModel $model the data model
	 * @return bool if the result is FALSE, the response will not be output
	 */
	public function postRequest(
		SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model
	);

}
