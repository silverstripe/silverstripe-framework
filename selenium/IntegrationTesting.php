<?php

class IntegrationTesting extends Controller {
	function index() {
		Director::redirect("sapphire/selenium/TestRunner.html?test=../../SeleniumTestSuite/&auto=true");	
	}
}