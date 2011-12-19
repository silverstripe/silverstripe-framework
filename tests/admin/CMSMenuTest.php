<?php
/**
 * @package cms
 * @subpackage tests
 */
class CMSMenuTest extends SapphireTest {
    
	public function testBasicMenuHandling() {
		// Clear existing menu
		CMSMenu::clear_menu();
		$menuItems = CMSMenu::get_menu_items();
		$this->assertTrue((empty($menuItems)), 'Menu can be cleared');
		
		// Add a controller to the menu and check it is as expected
		CMSMenu::add_controller('CMSMenuTest_LeftAndMainController');
		$menuItems = CMSMenu::get_menu_items();
		$menuItem = $menuItems['CMSMenuTest_LeftAndMainController'];
		$this->assertType('CMSMenuItem', $menuItem, 'Controller menu item is of class CMSMenuItem');
		$this->assertEquals($menuItem->url, singleton('CMSMenuTest_LeftAndMainController')->Link(), 'Controller menu item has the correct link');
		$this->assertEquals($menuItem->controller, 'CMSMenuTest_LeftAndMainController', 'Controller menu item has the correct controller class');
		$this->assertEquals($menuItem->priority, singleton('CMSMenuTest_LeftAndMainController')->stat('menu_priority'), 'Controller menu item has the correct priority');				
		CMSMenu::clear_menu();
		
		// Add a link to the menu
		CMSMenu::add_link('LinkCode', 'link title', 'http://www.example.com');
		$menuItems = CMSMenu::get_menu_items();
		$menuItem = $menuItems['LinkCode'];
		$this->assertType('CMSMenuItem', $menuItem, 'Link menu item is of class CMSMenuItem');
		$this->assertEquals($menuItem->title, 'link title', 'Link menu item has the correct title');
		$this->assertEquals($menuItem->url,'http://www.example.com', 'Link menu item has the correct link');
		$this->assertNull($menuItem->controller, 'Link menu item has no controller class');
		$this->assertEquals($menuItem->priority, -1, 'Link menu item has the correct priority');				
		CMSMenu::clear_menu();
		
	}

	public function testCmsClassDetection() {
	
		// Get CMS classes and check that:
		//	1.) SecurityAdmin is included
		//	2.) LeftAndMain & ModelAdmin are excluded
		$cmsClasses = CMSMenu::get_cms_classes();
		$this->assertContains('SecurityAdmin', $cmsClasses, 'SecurityAdmin included in valid CMS Classes');
		$this->assertNotContains('LeftAndMain', $cmsClasses, 'LeftAndMain not included in valid CMS Classes');
		$this->assertNotContains('ModelAdmin', $cmsClasses, 'LeftAndMain not included in valid CMS Classes');
	
	}

	public function testAdvancedMenuHandling() {
	
		// Populate from CMS Classes, check for existance of SecurityAdmin
		CMSMenu::clear_menu();
		CMSMenu::populate_menu();
		$menuItem = CMSMenu::get_menu_item('SecurityAdmin');
		$this->assertType('CMSMenuItem', $menuItem, 'SecurityAdmin menu item exists');
		$this->assertEquals($menuItem->url, singleton('SecurityAdmin')->Link(), 'Menu item has the correct link');
		$this->assertEquals($menuItem->controller, 'SecurityAdmin', 'Menu item has the correct controller class');
		$this->assertEquals(
			$menuItem->priority, 
			singleton('SecurityAdmin')->stat('menu_priority'), 
			'Menu item has the correct priority'
		);		
		
		// Check that menu order is correct by priority
		// Note this will break if populate_menu includes normal links (ie, as not controller)
		$menuItems = CMSMenu::get_menu_items();
		$priority = 9999; // ok, *could* be set larger, but shouldn't need to be!
		foreach($menuItems as $menuItem) {
			$this->assertEquals(
				$menuItem->priority, 
				singleton($menuItem->controller)->stat('menu_priority'), 
				"Menu item $menuItem->title has the correct priority"
			);			
			$this->assertLessThanOrEqual($priority, $menuItem->priority, 'Menu item is of lower or equal priority');
		}
	}
        
        /*
         * @description test that the array definiton of subnav classes exists
         */
        function testSubNavClassesExist() {
            $subNavControllerClassNames = CMSMain::$page_submenu_items_CMSMain;
            $this->assertTrue(is_array($subNavControllerClassNames),'Tests that submenu classname definition array exists');
            $this->assertGreaterThan(0,sizeof($subNavControllerClassNames),'Asserts submenu classname menu has values');
        }

        /*
         * @description test known main nav classes: Should return an array of ArrayLists
         */
        function testGetViewableSubmenuItems() {
            // Arguments
            $subNavControllerClassNames = CMSMain::$page_submenu_items_CMSMain;
            $mainNavControllerClassName = 'CMSMain';
            $mainNavIterator = 0;
            $instanceOfLeftAndMain = new LeftAndMain;
            // Method call
            $subNav = CMSMenu::get_viewable_submenu_items($subNavControllerClassNames[0],$mainNavControllerClassName,$mainNavIterator,$instanceOfLeftAndMain);

            // 1). Assert a complete array returned
            $this->assertNotEmpty($subNav,'Assert that array of subnav items is not empty');
            // 2). Assert first array element is of type ArrayList
            $this->assertTrue(is_object($subNav[0]),'Assert that first item of subnav array is an object');
            $this->assertEquals(get_class($subNav[0]),'ArrayList','Assert that first item of subnav array is of type ArrayList');
            // 3). Assert first ArrayList key 'items' is an array 
            $this->assertTrue(is_array($subNav[0]->items),'Assert that the array containing each individual subnav item, is an array');
            // 4). Assert first ArrayList key 'items' array has >0 values
            $this->assertGreaterThan(0,sizeof($subNav[0]->items),'Assert that the array of subnav items actually contains some items');
        }         

}

class CMSMenuTest_LeftAndMainController extends LeftAndMain implements TestOnly {
	static $url_segment = 'CMSMenuTest_LeftAndMainController';
	static $menu_title = 'CMSMenuTest_LeftAndMainController';
	static $menu_priority = 50;
}