<?php

class HTTPRequestTest extends SapphireTest {
	static $fixture_file = null;

	public function testHttpMethodOverrides() {
		$request = new SS_HTTPRequest(
			'GET',
			'admin/crm'
		);
		$this->assertTrue(
			$request->isGET(),
			'GET with no method override'
		);

		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm'
		);
		$this->assertTrue(
			$request->isPOST(),
			'POST with no method override'
		);

		$request = new SS_HTTPRequest('GET', 'admin/crm', null, array(
			'get' => array('_method' => 'DELETE')
		));
		$this->assertTrue(
			$request->isGET(),
			'GET with invalid POST method override'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			null,
			array('post' => array('_method' => 'DELETE'))
		);
		$this->assertTrue(
			$request->isDELETE(),
			'POST with valid method override to DELETE'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('post' => array('_method' => 'put'))
		);
		$this->assertTrue(
			$request->isPUT(),
			'POST with valid method override to PUT'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('post' => array('_method' => 'head'))
		);
		$this->assertTrue(
			$request->isHEAD(),
			'POST with valid method override to HEAD '
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('post' => array('_method' => 'head'))
		);
		$this->assertTrue(
			$request->isHEAD(),
			'POST with valid method override to HEAD'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('get' => array('post' => array('_method' => 'head')))
		);
		$this->assertTrue(
			$request->isPOST(),
			'POST with invalid method override by GET parameters to HEAD'
		);

		$this->setExpectedException('SS_HTTPResponse_Exception');
		$request = new SS_HTTPRequest('POST', null, null, array(
			'post' => array('_method' => 'invalid')
		));

		$request = new SS_HTTPRequest('POST', null, null, array(
			'server' => array('X_HTTP_METHOD_OVERRIDE' => 'put')
		));
		$this->assertTrue($request->isPut(), 'The can can be overriden with a header');

		$this->setExpectedException('SS_HTTPResponse_Exception');
		$request = new SS_HTTPRequest('POST', null, null, array(
			'server' => array('X_HTTP_METHOD_OVERRIDE' => 'invalid')
		));

		$request = new SS_HTTPRequest(null, null, null, array(
			'server' => array('REQUEST_METHOD' => 'POST')
		));
		$this->assertTrue($request->isPost(), 'The method defaults to the request method');
	}
	
	public function testRequestVars() {
		$getVars = array(
			'first' => 'a',
			'second' => 'b',
		);
		$postVars = array(
			'third' => 'c',
			'fourth' => 'd',
		);
		$requestVars = array(
			'first' => 'a',
			'second' => 'b',
			'third' => 'c',
			'fourth' => 'd',
		);
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			null,
			array('get' => $getVars, 'post' => $postVars)
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'GET parameters should supplement POST parameters'
		);
		
		$getVars = array(
			'first' => 'a',
			'second' => 'b',
		);
		$postVars = array(
			'first' => 'c',
			'third' => 'd',
		);
		$requestVars = array(
			'first' => 'c',
			'second' => 'b',
			'third' => 'd',
		);
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			null,
			array('get' => $getVars, 'post' => $postVars)
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'POST parameters should override GET parameters'
		);
		
		$getVars = array(
			'first' => array(
				'first' => 'a',
			),
			'second' => array(
				'second' => 'b',
			),
		);
		$postVars = array(
			'first' => array(
				'first' => 'c',
			),
			'third' => array(
				'third' => 'd',
			),
		);
		$requestVars = array(
			'first' => array(
				'first' => 'c',
			),
			'second' => array(
				'second' => 'b',
			),
			'third' => array(
				'third' => 'd',
			),
		);
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			null,
			array('get' => $getVars, 'post' => $postVars)
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'Nested POST parameters should override GET parameters'
		);
		
		$getVars = array(
			'first' => array(
				'first' => 'a',
			),
			'second' => array(
				'second' => 'b',
			),
		);
		$postVars = array(
			'first' => array(
				'second' => 'c',
			),
			'third' => array(
				'third' => 'd',
			),
		);
		$requestVars = array(
			'first' => array(
				'first' => 'a',
				'second' => 'c',
			),
			'second' => array(
				'second' => 'b',
			),
			'third' => array(
				'third' => 'd',
			),
		);
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			null,
			array('get' => $getVars, 'post' => $postVars)
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'Nested GET parameters should supplement POST parameters'
		);
	}

	public function testIsAjax() {
		$req = new SS_HTTPRequest('GET', '/', null, array('get' => array('ajax' => 0)));
		$this->assertFalse($req->isAjax());

		$req = new SS_HTTPRequest('GET', '/', null, array('get' => array('ajax' => 1)));
		$this->assertTrue($req->isAjax());

		$req = new SS_HTTPRequest('GET', '/');
		$req->setHeader('X-Requested-With', 'XMLHttpRequest');
		$this->assertTrue($req->isAjax());
	}

	public function testGetURL() {
		$req = new SS_HTTPRequest('GET', '/');
		$this->assertEquals('', $req->getURL());

		$req = new SS_HTTPRequest('GET', '/assets/somefile.gif');
		$this->assertEquals('assets/somefile.gif', $req->getURL());

		$req = new SS_HTTPRequest('GET', '/home?test=1');
		$this->assertEquals('home?test=1', $req->getURL(true));
		$this->assertEquals('home', $req->getURL());
	}

	public function testGetExtension() {
		$request = new SS_HTTPRequest('GET', '/path');
		$this->assertEquals('', $request->getExtension());

		$request = new SS_HTTPRequest('GET', '/path.extension');
		$this->assertEquals('extension', $request->getExtension());

		$request = new SS_HTTPRequest('GET', '/path.extension?get=value');
		$this->assertEquals('extension', $request->getExtension());
	}

	public function testShifting() {
		$request = new SS_HTTPRequest('GET', '/first/second/third');
		$this->assertEquals(array('first', 'second', 'third'), $request->getUrlParts());
		$this->assertEquals('first/second/third', $request->getRemainingUrl());
		$this->assertFalse($request->isAllRouted());

		$this->assertEquals('first', $request->shift());
		$this->assertEquals(array('second', 'third'), $request->getUrlParts());
		$this->assertEquals('second/third', $request->getRemainingUrl());
		$this->assertFalse($request->isAllRouted());

		$this->assertEquals(array('second', 'third'), $request->shift(2));
		$this->assertEquals('', $request->getRemainingUrl());
		$this->assertEquals(array(), $request->getUrlParts());
		$this->assertTrue($request->isAllRouted());
	}

	public function testParams() {
		$request = new SS_HTTPRequest('GET', '');

		$first = array('a' => '1');
		$second = array('a' => '', 'b' => '2');
		$third = array('c' => '3', 'd' => '4');

		$this->assertEquals(array(), $request->getParams());
		$this->assertEquals(array(), $request->getLatestParams());

		$request->pushParams($first);
		$this->assertEquals($first, $request->getParams());
		$this->assertEquals($first, $request->getLatestParams());

		$request->pushParams($second);
		$this->assertEquals(array('a' => '1', 'b' => '2'), $request->getParams());
		$this->assertEquals($second, $request->getLatestParams());
		$this->assertEquals('1', $request->getParam('a'));
		$this->assertEquals('', $request->getLatestParam('a'));

		$request->pushParams($third);
		$request->shiftParams();
		$this->assertEquals(array('a' => '2', 'b' => '3', 'c' => '4', 'd' => null), $request->getParams());
		$request->shiftParams();
		$this->assertEquals(array('a' => '3', 'b' => '4', 'c' => null, 'd' => null), $request->getParams());
	}

	public function testIsAllRouted() {
		$request = new SS_HTTPRequest('GET', 'first/second');
		$this->assertFalse($request->isAllRouted());

		$request->setUnshiftedButParsed(1);
		$this->assertFalse($request->isAllRouted());

		$request->setUnshiftedButParsed(2);
		$this->assertTrue($request->isAllRouted());
	}

	/**
	 * @covers SS_HTTPRequest::extractHeaders()
	 */
	public function testExtractRequestHeaders() {
		$request = array(
			'REDIRECT_STATUS'      => '200',
			'HTTP_HOST'            => 'host',
			'HTTP_USER_AGENT'      => 'User Agent',
			'HTTP_ACCEPT'          => 'text/html',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us',
			'HTTP_COOKIE'          => 'PastMember=1',
			'SERVER_PROTOCOL'      => 'HTTP/1.1',
			'REQUEST_METHOD'       => 'GET',
			'REQUEST_URI'          => '/',
			'SCRIPT_NAME'          => FRAMEWORK_DIR . '/main.php',
			'CONTENT_TYPE'         => 'text/xml',
			'CONTENT_LENGTH'       => 10
		);

		$headers = array(
			'Host'            => 'host',
			'User-Agent'      => 'User Agent',
			'Accept'          => 'text/html',
			'Accept-Language' => 'en-us',
			'Cookie'          => 'PastMember=1',
			'Content-Type'    => 'text/xml',
			'Content-Length'  => '10'
		);

		$request = new SS_HTTPRequest(null, null, null, array(
			'server' => $request
		));

		foreach($headers as $header => $expect) {
			$this->assertEquals($expect, $request->getHeader($header));
		}
	}

}
