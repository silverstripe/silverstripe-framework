<?php
/**
 * @package framework
 * @subpackage tests
 */
class HTTPResponseTest extends SapphireTest {
	
	function testStatusDescriptionStripsNewlines() {
		$r = new SS_HTTPResponse('my body', 200, "my description \nwith newlines \rand carriage returns");
		$this->assertEquals(
			"my description with newlines and carriage returns",
			$r->getStatusDescription()
		);
	}
	
	function testContentLengthHeader() {
		$r = new SS_HTTPResponse('123ü');
		$this->assertNotNull($r->getHeader('Content-Length'), 'Content-length header is added');
		$this->assertEquals(
			5, 
			$r->getHeader('Content-Length'),
			'Header matches actual content length in bytes'
		);
		
		$r->setBody('1234ü');
		$this->assertEquals(
			6, 
			$r->getHeader('Content-Length'),
			'Header is updated when body is changed'
		);
	}
	
}
