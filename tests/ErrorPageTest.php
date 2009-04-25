<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ErrorPageTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/ErrorPageTest.yml';
	
	function test404ErrorPage() {
		$page = $this->objFromFixture('ErrorPage', '404');
		
		/* The page is an instance of ErrorPage */
		$this->assertTrue($page instanceof ErrorPage, 'The page is an instance of ErrorPage');
		
		$response = $this->get($page->URLSegment);
		
		/* We have body text from the error page */
		$this->assertNotNull($response->getBody(), 'We have body text from the error page');

		/* Status code of the HTTPResponse for error page is "404" */
		$this->assertEquals($response->getStatusCode(), '404', 'Status cod eof the HTTPResponse for error page is "404"');
		
		/* Status message of the HTTPResponse for error page is "Not Found" */
		$this->assertEquals($response->getStatusDescription(), 'Not Found', 'Status message of the HTTResponse for error page is "Not found"');
	}
	
}
?>