<?php

class HTTPRequestTest extends SapphireTest {
	static $fixture_file = null;
	
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
	
}