<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ErrorPageTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/ErrorPageTest.yml';
	
	function test404ErrorPage() {
		$page = $this->objFromFixture('ErrorPage', '404');

		$response = $this->get($page->URLSegment);
		
		/* A standard error is shown */
		$this->assertEquals($response->getBody(), 'The requested page couldn\'t be found.', 'A standard error is shown');

		/* When the page is published, an error page with the theme is shown instead */		
		$page->publish('Stage', 'Live', false);
		
		$response = $this->get($page->URLSegment);
		
		/* There is body text from the error page */
		$this->assertNotNull($response->getBody(), 'We have body text from the error page');
		
		/* Status code of the HTTPResponse for error page is "404" */
		$this->assertEquals($response->getStatusCode(), '404', 'Status cod eof the HTTPResponse for error page is "404"');
		
		/* Status message of the HTTPResponse for error page is "Not Found" */
		$this->assertEquals($response->getStatusDescription(), 'Not Found', 'Status message of the HTTResponse for error page is "Not found"');
	}
	
	function testBehaviourOfShowInMenuAndShowInSearchFlags() {
		$page = $this->objFromFixture('ErrorPage', '404');
		
		/* Don't show the error page in the menus */
		$this->assertEquals($page->ShowInMenus, 0, 'Don\'t show the error page in the menus');
		
		/* Don't show the error page in the search */
		$this->assertEquals($page->ShowInSearch, 0, 'Don\'t show the error page in search');
	}
	
}
?>