<?php
class PjaxResponseNegotiatorTest extends SapphireTest {
	
	function testDefaultCallbacks() {
		$negotiator = new PjaxResponseNegotiator(array(
			'default' => function() {return 'default response';},
		));
		$request = new SS_HTTPRequest('GET', '/'); // not setting pjax header
		$this->assertEquals('default response', $negotiator->respond($request));
	}

	function testSelectsFragmentByHeader() {
		$negotiator = new PjaxResponseNegotiator(array(
			'default' => function() {return 'default response';},
			'myfragment' => function() {return 'myfragment response';},
		));
		$request = new SS_HTTPRequest('GET', '/');
		$request->addHeader('X-Pjax', 'myfragment');
		$this->assertEquals('myfragment response', $negotiator->respond($request));
	}

}