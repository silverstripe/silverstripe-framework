<?php

class ErrorPageTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/ErrorPageTest.yml';
	
	function test404ErrorPage() {
		$errorPage = DataObject::get_one('ErrorPage', "ErrorCode = '404'");

		/* We have an ErrorPage object to use */
		$this->assertTrue($errorPage instanceof ErrorPage);
		
		/* Test the URL of the error page out to get a response */
		$response = Director::test(Director::makeRelative($errorPage->Link()));
		
		/* We have an HTTPResponse object for the error page */
		$this->assertTrue($response instanceof HTTPResponse);
		
		/* We have body text from the error page */
		$this->assertTrue($response->getBody() != null);

		/* Status code of the HTTPResponse for error page is "404" */
		$this->assertTrue($response->getStatusCode() == '404');
		
		/* Status message of the HTTPResponse for error page is "Not Found" */
		$this->assertTrue($response->getStatusDescription() == 'Not Found');
	}
	
}

?>