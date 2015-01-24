<?php
/**
 * @package framework
 * @subpackage tests
 */
class HTTPResponseTest extends SapphireTest {

	public function testStatusDescriptionStripsNewlines() {
		$r = new SS_HTTPResponse('my body', 200, "my description \nwith newlines \rand carriage returns");
		$this->assertEquals(
			"my description with newlines and carriage returns",
			$r->getStatusDescription()
		);
	}

	public function testHTTPResponseException() {
		$response = new SS_HTTPResponse("Test", 200, 'OK');

		// Confirm that the exception's statusCode and statusDescription take precedence
		try {
			throw new SS_HTTPResponse_Exception($response, 404, 'not even found');

		} catch(SS_HTTPResponse_Exception $e) {
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
			throw new SS_HTTPResponse_Exception("Some content that may be from a hacker", 404, 'not even found');

		} catch(SS_HTTPResponse_Exception $e) {
			$this->assertEquals("text/plain", $e->getResponse()->getHeader("Content-Type"));
			return;
		}
		// Fail if we get to here
		$this->assertFalse(true, 'Something went wrong with our test exception');

	}
}
