<?php
/**
 * @package cms
 * @subpackage tests
 */
class CMSMenuTest extends SapphireTest implements TestOnly {

	public function testBasicMenuHandling() {
	
		// Clear existing menu
		CMSMenu::clear_menu();
		$menuItems = CMSMenu::get_menu_items();
		$this->assertTrue((empty($menuItems)), 'Menu can be cleared');
		
		// Add a controller to the menu and check it is as expected
		CMSMenu::add_controller('CMSMain');
		$menuItems = CMSMenu::get_menu_items();
		$menuItem = $menuItems['CMSMain'];
		$this->assertType('CMSMenuItem', $menuItem, 'Controller menu item is of class CMSMenuItem');
		$this->assertEquals($menuItem->url, singleton('CMSMain')->Link(), 'Controller menu item has the correct link');
		$this->assertEquals($menuItem->controller, 'CMSMain', 'Controller menu item has the correct controller class');
		$this->assertEquals($menuItem->priority, singleton('CMSMain')->stat('menu_priority'), 'Controller menu item has the correct priority');				
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
		//	1.) CMSMain is included
		//	2.) LeftAndMain & ModelAdmin are excluded
		$cmsClasses = CMSMenu::get_cms_classes();
		$this->assertContains('CMSMain', $cmsClasses, 'CMSMain included in valid CMS Classes');
		$this->assertNotContains('LeftAndMain', $cmsClasses, 'LeftAndMain not included in valid CMS Classes');
		$this->assertNotContains('ModelAdmin', $cmsClasses, 'LeftAndMain not included in valid CMS Classes');
	
	}

	public function testAdvancedMenuHandling() {
	
		// Populate from CMS Classes, check for existance of CMSMain
		CMSMenu::clear_menu();
		CMSMenu::populate_menu();
		$menuItem = CMSMenu::get_menu_item('CMSMain');
		$this->assertType('CMSMenuItem', $menuItem, 'CMSMain menu item exists');
		$this->assertEquals($menuItem->url, singleton('CMSMain')->Link(), 'Menu item has the correct link');
		$this->assertEquals($menuItem->controller, 'CMSMain', 'Menu item has the correct controller class');
		$this->assertEquals(
			$menuItem->priority, 
			singleton('CMSMain')->stat('menu_priority'), 
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

}