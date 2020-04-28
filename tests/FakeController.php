<?php
// Fake a current controller. Way harder than it should be
class FakeController extends Controller {

	public function __construct() {
		parent::__construct();

		$session = Injector::inst()->create('Session', isset($_SESSION) ? $_SESSION : array());
		$this->setSession($session);

		$this->pushCurrent();

		$request = new SS_HTTPRequest($_SERVER['REQUEST_METHOD'], '/');
		$this->setRequest($request);

		$this->setResponse(new SS_HTTPResponse());

		$this->init();
	}
}
