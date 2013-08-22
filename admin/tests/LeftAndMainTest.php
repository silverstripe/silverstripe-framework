<?php

/**
 * @package framework
 * @subpackage tests
 */
class LeftAndMainTest extends FunctionalTest {

	protected static $fixture_file = 'LeftAndMainTest.yml';
	
	protected $extraDataObjects = array('LeftAndMainTest_Object');
	
	protected $backupCss, $backupJs, $backupCombined;

	public function setUp() {
		parent::setUp();
		
		// @todo fix controller stack problems and re-activate
		//$this->autoFollowRedirection = false;
		CMSMenu::populate_menu();

		$this->backupCss = Config::inst()->get('LeftAndMain', 'extra_requirements_css');
		$this->backupJs = Config::inst()->get('LeftAndMain', 'extra_requirements_javascript');
		$this->backupCombined = Requirements::get_combined_files_enabled();

		Config::inst()->update('LeftAndMain', 'extra_requirements_css', array(
			FRAMEWORK_DIR . '/tests/assets/LeftAndMainTest.css'
		));

		Config::inst()->update('LeftAndMain', 'extra_requirements_javascript', array(
			FRAMEWORK_DIR . '/tests/assets/LeftAndMainTest.js'
		));

		Requirements::set_combined_files_enabled(false);
	}

	public function tearDown() {
		parent::tearDown();

		Config::inst()->update('LeftAndMain', 'extra_requirements_css', $this->backupCss);
		Config::inst()->update('LeftAndMain', 'extra_requirements_javascript', $this->backupJs);

		Requirements::set_combined_files_enabled($this->backupCombined);
	}
	

	public function testExtraCssAndJavascript() {
		$admin = $this->objFromFixture('Member', 'admin');
		$this->session()->inst_set('loggedInAs', $admin->ID);
		$response = $this->get('LeftAndMainTest_Controller');

		$this->assertRegExp('/tests\/assets\/LeftAndMainTest.css/i', $response->getBody(),
			"body should contain custom css");
		$this->assertRegExp('/tests\/assets\/LeftAndMainTest.js/i', $response->getBody(),
			"body should contain custom js");
	}

	/**
	 * Note: This test would typically rely on SiteTree and CMSMain, but is mocked by
	 * LeftAndMain_Controller and LeftAndMain_Object here to remove this dependency.
	 */
	public function testSaveTreeNodeSorting() {
		$this->loginWithPermission('ADMIN');
		
		// forcing sorting for non-MySQL		
		$rootPages = DataObject::get('LeftAndMainTest_Object', '"ParentID" = 0', '"ID"');
		$siblingIDs = $rootPages->column('ID');
		$page1 = $rootPages->offsetGet(0);
		$page2 = $rootPages->offsetGet(1);
		$page3 = $rootPages->offsetGet(2);
		
		// Move page2 before page1
		$siblingIDs[0] = $page2->ID;
		$siblingIDs[1] = $page1->ID;
		$data = array(
			'SiblingIDs' => $siblingIDs,
			'ID' => $page2->ID,
			'ParentID' => 0
		);

		$response = $this->post('LeftAndMainTest_Controller/savetreenode', $data);
		$this->assertEquals(200, $response->getStatusCode());
		$page1 = DataObject::get_by_id('LeftAndMainTest_Object', $page1->ID, false);
		$page2 = DataObject::get_by_id('LeftAndMainTest_Object', $page2->ID, false);
		$page3 = DataObject::get_by_id('LeftAndMainTest_Object', $page3->ID, false);
		
		$this->assertEquals(2, $page1->Sort, 'Page1 is sorted after Page2');
		$this->assertEquals(1, $page2->Sort, 'Page2 is sorted before Page1');
		$this->assertEquals(3, $page3->Sort, 'Sort order for other pages is unaffected');
	}
	
	public function testSaveTreeNodeParentID() {
		$this->loginWithPermission('ADMIN');

		$page1 = $this->objFromFixture('LeftAndMainTest_Object', 'page1');
		$page2 = $this->objFromFixture('LeftAndMainTest_Object', 'page2');
		$page3 = $this->objFromFixture('LeftAndMainTest_Object', 'page3');
		$page31 = $this->objFromFixture('LeftAndMainTest_Object', 'page31');
		$page32 = $this->objFromFixture('LeftAndMainTest_Object', 'page32');

		// Move page2 into page3, between page3.1 and page 3.2
		$siblingIDs = array(
			$page31->ID,
			$page2->ID,
			$page32->ID
		);
		$data = array(
			'SiblingIDs' => $siblingIDs,
			'ID' => $page2->ID,
			'ParentID' => $page3->ID
		);
		$response = $this->post('LeftAndMainTest_Controller/savetreenode', $data);
		$this->assertEquals(200, $response->getStatusCode());
		$page2 = DataObject::get_by_id('LeftAndMainTest_Object', $page2->ID, false);
		$page31 = DataObject::get_by_id('LeftAndMainTest_Object', $page31->ID, false);
		$page32 = DataObject::get_by_id('LeftAndMainTest_Object', $page32->ID, false);

		$this->assertEquals($page3->ID, $page2->ParentID, 'Moved page gets new parent');
		$this->assertEquals(1, $page31->Sort, 'Children pages before insertaion are unaffected');
		$this->assertEquals(2, $page2->Sort, 'Moved page is correctly sorted');
		$this->assertEquals(3, $page32->Sort, 'Children pages after insertion are resorted');
	}
	
	/**
	 * Check that all subclasses of leftandmain can be accessed
	 */
	public function testLeftAndMainSubclasses() {
		$adminuser = $this->objFromFixture('Member','admin');
		$this->session()->inst_set('loggedInAs', $adminuser->ID);
		
		$menuItems = singleton('LeftAndMain')->MainMenu();
		foreach($menuItems as $menuItem) {
			$link = $menuItem->Link;
			
			// don't test external links
			if(preg_match('/^https?:\/\//',$link)) continue;

			$response = $this->get($link);
			
			$this->assertInstanceOf('SS_HTTPResponse', $response, "$link should return a response object");
			$this->assertEquals(200, $response->getStatusCode(), "$link should return 200 status code");
			// Check that a HTML page has been returned
			$this->assertRegExp('/<html[^>]*>/i', $response->getBody(), "$link should contain <html> tag");
			$this->assertRegExp('/<head[^>]*>/i', $response->getBody(), "$link should contain <head> tag");
			$this->assertRegExp('/<body[^>]*>/i', $response->getBody(), "$link should contain <body> tag");
		}
		
		$this->session()->inst_set('loggedInAs', null);

	}

	public function testCanView() {
		$adminuser = $this->objFromFixture('Member', 'admin');
		$securityonlyuser = $this->objFromFixture('Member', 'securityonlyuser');
		$allcmssectionsuser = $this->objFromFixture('Member', 'allcmssectionsuser');
		$allValsFn = create_function('$obj', 'return $obj->getValue();');
		
		// anonymous user
		$this->session()->inst_set('loggedInAs', null);
		$menuItems = singleton('LeftAndMain')->MainMenu(false);
		$this->assertEquals(
			array_map($allValsFn, $menuItems->column('Code')),
			array(),
			'Without valid login, members cant access any menu entries'
		);
		
		// restricted cms user
		$this->session()->inst_set('loggedInAs', $securityonlyuser->ID);
		$menuItems = singleton('LeftAndMain')->MainMenu(false);
		$this->assertEquals(
			array_map($allValsFn, $menuItems->column('Code')),
			array('SecurityAdmin','Help'),
			'Groups with limited access can only access the interfaces they have permissions for'
		);
		
		// all cms sections user
		$this->session()->inst_set('loggedInAs', $allcmssectionsuser->ID);
		$menuItems = singleton('LeftAndMain')->MainMenu(false);
		$this->assertContains('SecurityAdmin', 
			array_map($allValsFn, $menuItems->column('Code')),
			'Group with CMS_ACCESS_LeftAndMain permission can access all sections'
		);
		$this->assertContains('Help', 
			array_map($allValsFn, $menuItems->column('Code')),
			'Group with CMS_ACCESS_LeftAndMain permission can access all sections'
		);
		
		// admin
		$this->session()->inst_set('loggedInAs', $adminuser->ID);
		$menuItems = singleton('LeftAndMain')->MainMenu(false);
		$this->assertContains(
			'SecurityAdmin',
			array_map($allValsFn, $menuItems->column('Code')),
			'Administrators can access Security Admin'
		);
		
		$this->session()->inst_set('loggedInAs', null);
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class LeftAndMainTest_Controller extends LeftAndMain implements TestOnly {
	protected $template = 'BlankPage';
	
	private static $tree_class = 'LeftAndMainTest_Object';
}

/**
 * @package framework
 * @subpackage tests
 */
class LeftAndMainTest_Object extends DataObject implements TestOnly {
	
	private static $db = array(
		'Title' => 'Varchar',
		'URLSegment' => 'Varchar',
		'Sort' => 'Int',
	);
	
	private static $extensions = array(
		'Hierarchy'
	);
	
}
