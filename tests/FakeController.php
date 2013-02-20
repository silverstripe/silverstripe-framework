<?php

use SilverStripe\Framework\Http\Request;
use SilverStripe\Framework\Http\Response;
use SilverStripe\Framework\Http\Session;

// Fake a current controller. Way harder than it should be
class FakeController extends Controller {
	
	public function __construct() {
		parent::__construct();

		$session = new Session(isset($_SESSION) ? $_SESSION : null);
		$this->setSession($session);
		
		$this->pushCurrent();

		$this->request = new Request(
			(isset($_SERVER['X-HTTP-Method-Override'])) 
				? $_SERVER['X-HTTP-Method-Override'] 
				: $_SERVER['REQUEST_METHOD'],
			'/'
		);

		$this->response = new Response();
		
		$this->init();
	}
}