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
			$_SERVER['HTTP_HOST'] = $domain;
			$this->assertEquals(
				$urlSegment, 
				RootURLController::get_homepage_urlsegment(Translatable::default_locale()), 
				"Testing $domain matches $urlSegment"
			);
		}
		
		$_SERVER['HTTP_HOST'] = $originalHost;
	}
}