<?php

class RestfulServerTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/api/RestfulServerTest.yml';
	
	function testCreate() {
		// Test GET
		$pageID = $this->idFromFixture('Page', 'page1');

		$page1 = Director::test("api/v1/Page/$pageID", 'GET');
		$page1xml = new RestfulService_Response($page1->getBody(), $page1->getStatusCode());

		// Test fields
		$this->assertEquals(200, $page1xml->getStatusCode());
		$this->assertEquals('First Page', (string)$page1xml->xpath_one('/Page/Title'));

		// Test has_many relationships
		$comments = $page1xml->xpath('/Page/Comments/PageComment');
		$this->assertEquals(Director::absoluteURL('api/v1/PageComment/3'), (string)$comments[0]['href']);
		$this->assertEquals(Director::absoluteURL('api/v1/PageComment/4'), (string)$comments[1]['href']);
		$this->assertEquals(3, (string)$comments[0]['id']);
		$this->assertEquals(4, (string)$comments[1]['id']);

		/// Test has_one relationships
		$parent = $page1xml->xpath_one('/Page/Parent');
		$this->assertEquals(Director::absoluteURL('api/v1/SiteTree/1'), (string)$parent['href']);
		$this->assertEquals(1, (string)$parent['id']);
		
		/*
		$deletion = $service->get('Page/1', 'DELETE');
		if($deletion->successfulStatus()) {
			echo 'deleted';
		} else {
			switch($deletion->statusCode()) {
				case 403: echo "You don't have permission to delete that object"; break;
				default: echo "There was an error deleting"; break;
			}
		}
		*/

	}
}