<?php

class HTTPRequestTest extends SapphireTest {
	
	static $fixture_file = null;

	function testUrlNormalisation() {
		$this->assertEquals(
			'leading/slash',
			Object::create('SS_HTTPRequest', "GET", "/leading/slash")->getURL()
		);
		$this->assertEquals(
			'multipleleading/slash',
			Object::create('SS_HTTPRequest', "GET", "//multipleleading/slash")->getURL()
		);
		$this->assertEquals(
			'trailing/slash/',
			Object::create('SS_HTTPRequest', "GET", "trailing/slash/")->getURL()
		);
		$this->assertEquals(
			'multipletrailing/slash/',
			Object::create('SS_HTTPRequest', "GET", "multipletrailing/slash//")->getURL()
		);
		$this->assertEquals(
			'multiple/part/slash/',
			Object::create('SS_HTTPRequest', "GET", 'multiple//part//slash/')->getURL()
		);
	}
	
	function testMatch() {
		$request = new SS_HTTPRequest("GET", "admin/crm/add");
		
		/* When a rule matches, but has no variables, array("_matched" => true) is returned. */
		$this->assertEquals(array("_matched" => true), $request->match('admin/crm', true));
		
		/* Becasue we shifted admin/crm off the stack, just "add" should be remaining */
		$this->assertEquals("add", $request->remaining());
		
		$this->assertEquals(array("_matched" => true), $request->match('add', true));
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
			array('_method' => 'DELETE')
		);
		$this->assertTrue(
			$request->isGET(),
			'GET with invalid POST method override'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('_method' => 'DELETE')
		);
		$this->assertTrue(
			$request->isDELETE(),
			'POST with valid method override to DELETE'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('_method' => 'put')
		);
		$this->assertTrue(
			$request->isPUT(),
			'POST with valid method override to PUT'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('_method' => 'head')
		);
		$this->assertTrue(
			$request->isHEAD(),
			'POST with valid method override to HEAD '
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array(),
			array('_method' => 'head')
		);
		$this->assertTrue(
			$request->isHEAD(),
			'POST with valid method override to HEAD'
		);
		
		$request = new SS_HTTPRequest(
			'POST',
			'admin/crm',
			array('_method' => 'head')
		);
		$this->assertTrue(
			$request->isPOST(),
			'POST with invalid method override by GET parameters to HEAD'
		);
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
			$getVars,
			$postVars
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
			$getVars,
			$postVars
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
			$getVars,
			$postVars
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
			$getVars,
			$postVars
		);
		$this->assertEquals(
			$requestVars,
			$request->requestVars(),
			'Nested GET parameters should supplement POST parameters'
		);
	}
}
