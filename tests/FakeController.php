<?php
// Fake a current controller. Way harder than it should be
class FakeController extends Controller {
	
	function __construct() {
		parent::__construct();

		$session = new Session(isset($_SESSION) ? $_SESSION : null);
		$this->setSession($session);
		
		$this->pushCurrent();

		$this->request = new SS_HTTPRequest(
			(isset($_SERVER['X-HTTP-Method-Override'])) ? $_SERVER['X-HTTP-Method-Override'] : $_SERVER['REQUEST_METHOD'],
			'/'
		);

		$this->response = new SS_HTTPResponse();
		
		$this->init();
	}
}