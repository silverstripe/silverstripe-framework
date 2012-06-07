<?php
class PjaxResponseNegotiatorTest extends SapphireTest {
	
	function testDefaultCallbacks() {
		$negotiator = new PjaxResponseNegotiator(array(
			'default' => function() {return 'default response';},
		));
		$request = new SS_HTTPRequest('GET', '/'); // not setting pjax header
		$response = $negotiator->respond($request);
		$this->assertEquals('default response', $response->getBody());
	}

	function testSelectsFragmentByHeader() {
		$negotiator = new PjaxResponseNegotiator(array(
			'default' => function() {return 'default response';},
			'myfragment' => function() {return 'myfragment response';},
		));
		$request = new SS_HTTPRequest('GET', '/');
		$request->addHeader('X-Pjax', 'myfragment');
		$response = $negotiator->respond($request);
		$this->assertEquals('{"myfragment":"myfragment response"}', $response->getBody());
	}

	function testMultipleFragments() {
		$negotiator = new PjaxResponseNegotiator(array(
			'default' => function() {return 'default response';},
			'myfragment' => function() {return 'myfragment response';},
			'otherfragment' => function() {return 'otherfragment response';},
		));
		$request = new SS_HTTPRequest('GET', '/');
		$request->addHeader('X-Pjax', 'myfragment,otherfragment');
		$request->addHeader('Accept', 'text/json');
		$response = $negotiator->respond($request);
		$json = json_decode( $response->getBody());
		$this->assertObjectHasAttribute('myfragment', $json);
		$this->assertEquals('myfragment response', $json->myfragment);
		$this->assertObjectHasAttribute('otherfragment', $json);
		$this->assertEquals('otherfragment response', $json->otherfragment);
	}

}