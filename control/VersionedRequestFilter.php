<?php
/**
 * Initialises the versioned stage when a request is made.
 *
 * @package framework
 * @subpackage control
 */
class VersionedRequestFilter implements RequestFilter {

	public function preRequest(SS_HTTPRequest $req, Session $session, DataModel $model) {
		Versioned::choose_site_stage($session);
	}

	public function postRequest(SS_HTTPRequest $req, SS_HTTPResponse &$res, Session $session, DataModel $model) {
		// No-op
	}

	public function postShorted(SS_HTTPRequest $req, SS_HTTPResponse &$earlyRes, Session $session, DataModel $model) {
		// No-op
	}

}
