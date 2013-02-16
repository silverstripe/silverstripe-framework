<?php

use SilverStripe\Framework\Http\Response;
use SilverStripe\Framework\Http\ResponseException;

/**
 * @package framework
 * @subpackage tests
 */
class HTTPResponseTest extends SapphireTest {
	
	public function testStatusDescriptionStripsNewlines() {
		$r = new Response('my body', 200, "my description \nwith newlines \rand carriage returns");
		$this->assertEquals(
			"my description with newlines and carriage returns",
			$r->getStatusDescription()
		);
	}
	
	public function testContentLengthHeader() {
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
	
	public function testHTTPResponseException() {
		$response = new Response("Test", 200, 'OK');

		// Confirm that the exception's statusCode and statusDescription take precedence
		try {
			throw new ResponseException($response, 404, 'not even found');

		} catch(ResponseException $e) {
			$this->assertEquals(404, $e->getResponse()->getStatusCode());
			$this->assertEquals('not even found', $e->getResponse()->getStatusDescription());
			return;
		}
		// Fail if we get to here
		$this->assertFalse(true, 'Something went wrong with our test exception');

	}

	public function testExceptionContentPlainByDefault() {

		// Confirm that the exception's statusCode and statusDescription take precedence
		try {
			throw new ResponseException("Some content that may be from a hacker", 404, 'not even found');

		} catch(ResponseException $e) {
			$this->assertEquals("text/plain", $e->getResponse()->getHeader("Content-Type"));
			return;
		}
		// Fail if we get to here
		$this->assertFalse(true, 'Something went wrong with our test exception');

	}

	public function testIsRedirect() {
		$response = new Response();
		$this->assertFalse($response->isRedirect());
		$this->assertTrue($response->isRedirect(301));

		$response->setStatusCode(307);
		$this->assertTrue($response->isRedirect());
	}

}
