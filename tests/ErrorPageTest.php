<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ErrorPageTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/ErrorPageTest.yml';
	
	protected $orig = array();
	
	protected $tmpAssetsPath = '';
	
	function setUp() {
		parent::setUp();
		
		$this->orig['ErrorPage_staticfilepath'] = ErrorPage::get_static_filepath();		
		$this->tmpAssetsPath = sprintf('%s/_tmp_assets_%s', TEMP_FOLDER, rand());
		Filesystem::makeFolder($this->tmpAssetsPath . '/ErrorPageTest');
		ErrorPage::set_static_filepath($this->tmpAssetsPath . '/ErrorPageTest');
		
		$this->orig['Director_environmenttype'] = Director::get_environment_type();
		Director::set_environment_type('live');
	}
	
	function tearDown() {
		parent::tearDown();
		
		ErrorPage::set_static_filepath($this->orig['ErrorPage_staticfilepath']);
		Director::set_environment_type($this->orig['Director_environmenttype']);
		
		Filesystem::removeFolder($this->tmpAssetsPath . '/ErrorPageTest');
		Filesystem::removeFolder($this->tmpAssetsPath);
	}
	
	function test404ErrorPage() {
		$page = $this->objFromFixture('ErrorPage', '404');
		// ensure that the errorpage exists as a physical file
		$page->publish('Stage', 'Live');
		
		$response = $this->get('nonexistent-page');
		
		/* We have body text from the error page */
		$this->assertNotNull($response->getBody(), 'We have body text from the error page');

		/* Status code of the SS_HTTPResponse for error page is "404" */
		$this->assertEquals($response->getStatusCode(), '404', 'Status code of the SS_HTTPResponse for error page is "404"');
		
		/* Status message of the SS_HTTPResponse for error page is "Not Found" */
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