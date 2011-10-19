<?php

/**
 * Description of AuthenticationFilter
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class AuthenticationFilter implements RequestFilter {
	public $authenticationService;
	
	static $injections = array(
		'authenticationService' => '#$AuthenticationService'
	);

	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {
		// lets try authenticating 
		if (isset($_REQUEST['auth']) || isset($_REQUEST['action_dologin'])) {
			$email = $request->requestVar('Email');
			$pass = $request->requestVar('Password');
			$member = $this->authenticationService->authenticate($email, $pass);
			if ($member) {
				$member->logIn($request->postVar('Remember'));
				// dirty hack for now... 
				$session->inst_set('loggedInAs', $member->ID);
			}

			// because we have the request here, we can analyse it to see if there's other
			// things we should do, such as a redirect back to a login form or
			// something completely different
		}
	}

	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {

	}

}