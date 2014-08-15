<?php
/**
 * Initialises the versioned stage when a request is made.
 *
 * @package framework
 * @subpackage control
 */
class VersionedRequestFilter implements RequestFilter {

	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {
		Versioned::choose_site_stage($session);
	}

	public function postShorted(
		SS_HTTPRequest $request,
		SS_HTTPResponse &$earlyResponse,
		Session $session,
		DataModel $model
	) {
		// No-op
	}

	public function postRequest(
		SS_HTTPRequest $request,
		SS_HTTPResponse &$response,
		Session $session,
		DataModel $model
	) {
		// No-op
	}

}
