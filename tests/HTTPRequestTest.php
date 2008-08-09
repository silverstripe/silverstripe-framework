<?php

class HTTPRequestTest extends SapphireTest {
	static $fixture_file = null;
	
	function testMatch() {
		$request = new HTTPRequest("GET", "admin/crm/add");
		
		/* When a rule matches, but has no variables, array("_matched" => true) is returned. */
		$this->assertEquals(array("_matched" => true), $request->match('admin/crm', true));
		
		/* Becasue we shifted admin/crm off the stack, just "add" should be remaining */
		$this->assertEquals("add", $request->remaining());
		
		$this->assertEquals(array("_matched" => true), $request->match('add', true));
	}
	
}