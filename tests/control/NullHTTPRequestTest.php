<?php
/**
 * @package framework
 * @subpackage tests
 */
class NullHTTPRequestTest extends SapphireTest {

	function testAllHttpVerbsAreFalse() {
		$r = new NullHTTPRequest();
		$this->assertFalse($r->isGET());
		$this->assertFalse($r->isPOST());
		$this->assertFalse($r->isPUT());
		$this->assertFalse($r->isDELETE());
		$this->assertFalse($r->isHEAD());
	}
	
	function testGetURL() {
		$r = new NullHTTPRequest();
		$this->assertEquals('', $r->getURL());
	}
	
}
