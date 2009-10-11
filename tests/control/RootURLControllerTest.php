<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class RootURLControllerTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/control/RootURLControllerTest.yml';
	
	function testHomepageForDomain() {
		$originalHost = $_SERVER['HTTP_HOST'];

		// Tests matching an HTTP_HOST value to URLSegment homepage values
		$tests = array(
			'page.co.nz' => 'page1',
			'www.page.co.nz' => 'page1',
			'help.com' => 'page1',
			'www.help.com' => 'page1',
			'something.com' => 'page1',
			'www.something.com' => 'page1',

	 		'other.co.nz' => 'page2',
	 		'www.other.co.nz' => 'page2',
			'right' => 'page2',
			'www. right' => 'page2',

			'only.com' => 'page3',
			'www.only.com' => 'page3',
			
			'www.somethingelse.com' => 'home',
			'somethingelse.com' => 'home',
			
			// Test some potential false matches to page2 and page3
			'alternate.only.com' => 'home',
			'www.alternate.only.com' => 'home',
			'alternate.something.com' => 'home',
		);
		
		foreach($tests as $domain => $urlSegment) {
			RootURLController::reset();
			$_SERVER['HTTP_HOST'] = $domain;
			
			$this->assertEquals(
				$urlSegment, 
				RootURLController::get_homepage_link(), 
				"Testing $domain matches $urlSegment"
			);
		}
		
		$_SERVER['HTTP_HOST'] = $originalHost;
	}
	
	public function testGetHomepageLink() {
		$default = $this->objFromFixture('Page', 'home');
		$nested  = $this->objFromFixture('Page', 'nested');
		
		SiteTree::disable_nested_urls();
		$this->assertEquals('home', RootURLController::get_homepage_link());
		SiteTree::enable_nested_urls();
		$this->assertEquals('home', RootURLController::get_homepage_link());
		
		$nested->HomepageForDomain = str_replace('www.', null, $_SERVER['HTTP_HOST']);
		$nested->write();
		
		RootURLController::reset();
		SiteTree::disable_nested_urls();
		$this->assertEquals('nested-home', RootURLController::get_homepage_link());
		
		RootURLController::reset();
		SiteTree::enable_nested_urls();
		$this->assertEquals('home/nested-home', RootURLController::get_homepage_link());
		
		$nested->HomepageForDomain = null;
		$nested->write();
	}
	
}