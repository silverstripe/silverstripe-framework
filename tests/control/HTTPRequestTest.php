<?php

class HTTPRequestTest extends SapphireTest {
	protected static $fixture_file = null;

	public function testMatch() {
		$request = new SS_HTTPRequest("GET", "admin/crm/add");

		/* When a rule matches, but has no variables, array("_matched" => true) is returned. */
		$this->assertEquals(["_matched" => true], $request->match('admin/crm', true));

		/* Becasue we shifted admin/crm off the stack, just "add" should be remaining */
		$this->assertEquals("add", $request->remaining());

		$this->assertEquals(["_matched" => true], $request->match('add', true));
	}

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

		$request = new SS_HTTPRequest(
			'GET',
			'admin/crm',
			['_method' => 'DELETE']
		);
		$this->assertTrue(
			$request->isGET(),
			'GET with invalid POST method override'
		);

		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			[],
			['_method' => 'DELETE']
		);
		$this->assertTrue(
			$request->isDELETE(),
			'POST with valid method override to DELETE'
		);

		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			[],
			['_method' => 'put']
		);
		$this->assertTrue(
			$request->isPUT(),
			'POST with valid method override to PUT'
		);

		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			[],
			['_method' => 'head']
		);
		$this->assertTrue(
			$request->isHEAD(),
			'POST with valid method override to HEAD '
		);

		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			[],
			['_method' => 'head']
		);
		$this->assertTrue(
			$request->isHEAD(),
			'POST with valid method override to HEAD'
		);

		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			['_method' => 'head']
		);
		$this->assertTrue(
			$request->isPOST(),
			'POST with invalid method override by GET parameters to HEAD'
		);
	}

	public function testRequestVars() {
		$getVars = [
			'first' => 'a',
			'second' => 'b',
		];
		$postVars = [
			'third' => 'c',
			'fourth' => 'd',
		];
		$requestVars = [
			'first' => 'a',
			'second' => 'b',
			'third' => 'c',
			'fourth' => 'd',
		];
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			$getVars,
			$postVars
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'GET parameters should supplement POST parameters'
		);

		$getVars = [
			'first' => 'a',
			'second' => 'b',
		];
		$postVars = [
			'first' => 'c',
			'third' => 'd',
		];
		$requestVars = [
			'first' => 'c',
			'second' => 'b',
			'third' => 'd',
		];
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			$getVars,
			$postVars
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'POST parameters should override GET parameters'
		);

		$getVars = [
			'first' => [
				'first' => 'a',
			],
			'second' => [
				'second' => 'b',
			],
		];
		$postVars = [
			'first' => [
				'first' => 'c',
			],
			'third' => [
				'third' => 'd',
			],
		];
		$requestVars = [
			'first' => [
				'first' => 'c',
			],
			'second' => [
				'second' => 'b',
			],
			'third' => [
				'third' => 'd',
			],
		];
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			$getVars,
			$postVars
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'Nested POST parameters should override GET parameters'
		);

		$getVars = [
			'first' => [
				'first' => 'a',
			],
			'second' => [
				'second' => 'b',
			],
		];
		$postVars = [
			'first' => [
				'second' => 'c',
			],
			'third' => [
				'third' => 'd',
			],
		];
		$requestVars = [
			'first' => [
				'first' => 'a',
				'second' => 'c',
			],
			'second' => [
				'second' => 'b',
			],
			'third' => [
				'third' => 'd',
			],
		];
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			$getVars,
			$postVars
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'Nested GET parameters should supplement POST parameters'
		);
	}

	public function testIsAjax() {
		$req = new SS_HTTPRequest('GET', '/', ['ajax' => 0]);
		$this->assertFalse($req->isAjax());

		$req = new SS_HTTPRequest('GET', '/', ['ajax' => 1]);
		$this->assertTrue($req->isAjax());

		$req = new SS_HTTPRequest('GET', '/');
		$req->addHeader('X-Requested-With', 'XMLHttpRequest');
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
}
