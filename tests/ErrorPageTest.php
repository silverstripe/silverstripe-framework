<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ErrorPageTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/ErrorPageTest.yml';
	
	function test404ErrorPage() {
		$errorPage = $this->objFromFixture('ErrorPage', '404');
		
		/* We have an ErrorPage object to use */
		$this->assertTrue($errorPage instanceof ErrorPage);
		
		/* Test the URL of the error page out to get a response */
		$response = $this->get($errorPage->URLSegment);
		
		/* We have an HTTPResponse object for the error page */
		$this->assertTrue($response instanceof HTTPResponse);
		
		/* We have body text from the error page */
		$this->assertNotNull($response->getBody(), 'We have body text from the error page');

		/* Status code of the HTTPResponse for error page is "404" */
		$this->assertEquals($response->getStatusCode(), '404', 'Status cod eof the HTTPResponse for error page is "404"');
		
		/* Status message of the HTTPResponse for error page is "Not Found" */
		$this->assertEquals($response->getStatusDescription(), 'Not Found', 'Status message of the HTTResponse for error page is "Not found"');
	}
	
}
?>