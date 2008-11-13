<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ModelAsControllerTest extends SapphireTest {
	
	public function testFindOldPage() {
		$page = new Page();
		$page->Title      = 'Test Page';
		$page->URLSegment = 'test-page';
		$page->write();
		$page->publish('Stage', 'Live');
		
		$page->URLSegment = 'test';
		$page->write();
		$page->publish('Stage', 'Live');
		
		$router   = new ModelAsController();
		$request  = new HTTPRequest(
			'GET', 'test-page/action/id/otherid'
		);
		$request->match('$URLSegment/$Action/$ID/$OtherID');
		$response = $router->handleRequest($request);
		
		$this->assertEquals (
			$response->getHeader('Location'),
			Controller::join_links(Director::baseURL() . 'test/action/id/otherid')
		);
	}
	
}
