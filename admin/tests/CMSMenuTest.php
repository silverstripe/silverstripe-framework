<?php

use SilverStripe\Admin\CMSMenu;
use SilverStripe\Admin\CMSMenuItem;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;

/**
 * @package framework
 * @subpackage tests
 */
class CMSMenuTest extends SapphireTest implements TestOnly {

	public function testBasicMenuHandling() {

		// Clear existing menu
		CMSMenu::clear_menu();
		$menuItems = CMSMenu::get_menu_items();
		$this->assertTrue((empty($menuItems)), 'Menu can be cleared');

		// Add a controller to the menu and check it is as expected
		CMSMenu::add_controller('CMSMenuTest_LeftAndMainController');
		$menuItems = CMSMenu::get_menu_items();
		$menuItem = $menuItems['CMSMenuTest_LeftAndMainController'];
		$this->assertInstanceOf('SilverStripe\\Admin\\CMSMenuItem', $menuItem, 'Controller menu item is of class CMSMenuItem');
		$this->assertContains($menuItem->url, singleton('CMSMenuTest_LeftAndMainController')->Link(),
			'Controller menu item has the correct link');
		$this->assertEquals($menuItem->controller, 'CMSMenuTest_LeftAndMainController',
			'Controller menu item has the correct controller class');
		$this->assertEquals($menuItem->priority, singleton('CMSMenuTest_LeftAndMainController')->stat('menu_priority'),
			'Controller menu item has the correct priority');
		CMSMenu::clear_menu();

		// Add another controller
		CMSMenu::add_controller('CMSMenuTest_CustomTitle');
		$menuItems = CMSMenu::get_menu_items();
		$menuItem = $menuItems['CMSMenuTest_CustomTitle'];
		$this->assertInstanceOf('SilverStripe\\Admin\\CMSMenuItem', $menuItem, 'Controller menu item is of class CMSMenuItem');
		$this->assertEquals('CMSMenuTest_CustomTitle (localised)', $menuItem->title);
		CMSMenu::clear_menu();

		// Add a link to the menu
		CMSMenu::add_link('LinkCode', 'link title', 'http://www.example.com');
		$menuItems = CMSMenu::get_menu_items();
		$menuItem = $menuItems['LinkCode'];
		$this->assertInstanceOf('SilverStripe\\Admin\\CMSMenuItem', $menuItem, 'Link menu item is of class CMSMenuItem');
		$this->assertEquals($menuItem->title, 'link title', 'Link menu item has the correct title');
		$this->assertEquals($menuItem->url,'http://www.example.com', 'Link menu item has the correct link');
		$this->assertNull($menuItem->controller, 'Link menu item has no controller class');
		$this->assertEquals($menuItem->priority, -1, 'Link menu item has the correct priority');
		CMSMenu::clear_menu();
	}

	public function testRemove() {
		CMSMenu::clear_menu();
		CMSMenu::add_menu_item('custom', 'Custom Title', 'custom');
		CMSMenu::add_menu_item('other', 'Other Section', 'other', 'CMSMenuTest_LeftAndMainController');
		$this->assertNotEmpty(CMSMenu::get_menu_items());

		CMSMenu::remove_menu_class('CMSMenuTest_LeftAndMainController');
		CMSMenu::remove_menu_item('custom');

		$this->assertEmpty(CMSMenu::get_menu_items());
	}

	public function testLinkWithExternalAttributes() {
		CMSMenu::clear_menu();

		CMSMenu::add_link('LinkCode', 'link title', 'http://www.example.com', -2, array(
			'target' => '_blank'
		));

		$menuItems = CMSMenu::get_menu_items();
		/** @var CMSMenuItem $menuItem */
		$menuItem = $menuItems['LinkCode'];

		$this->assertEquals('target="_blank"', $menuItem->getAttributesHTML());

		CMSMenu::clear_menu();
	}

	public function testCmsClassDetection() {

		// Get CMS classes and check that:
		//	1.) SecurityAdmin is included
		//	2.) LeftAndMain & ModelAdmin are excluded
		$cmsClasses = CMSMenu::get_cms_classes();
		$this->assertContains('SilverStripe\\Admin\\SecurityAdmin', $cmsClasses, 'SecurityAdmin included in valid CMS Classes');
		$this->assertNotContains('SilverStripe\\Admin\\LeftAndMain', $cmsClasses, 'LeftAndMain not included in valid CMS Classes');
		$this->assertNotContains('SilverStripe\\Admin\\ModelAdmin', $cmsClasses, 'LeftAndMain not included in valid CMS Classes');

	}

	public function testAdvancedMenuHandling() {

		// Populate from CMS Classes, check for existance of SecurityAdmin
		CMSMenu::clear_menu();
		CMSMenu::populate_menu();
		$menuItem = CMSMenu::get_menu_item('SilverStripe-Admin-SecurityAdmin');
		$this->assertInstanceOf('SilverStripe\\Admin\\CMSMenuItem', $menuItem, 'SecurityAdmin menu item exists');
		$this->assertContains($menuItem->url, SecurityAdmin::singleton()->Link(), 'Menu item has the correct link');
		$this->assertEquals($menuItem->controller, 'SilverStripe\\Admin\\SecurityAdmin', 'Menu item has the correct controller class');
		$this->assertEquals(
			$menuItem->priority,
			SecurityAdmin::singleton()->stat('menu_priority'),
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

/**
 * @package framework
 * @subpackage tests
 */
class CMSMenuTest_LeftAndMainController extends LeftAndMain implements TestOnly {

	private static $url_segment = 'CMSMenuTest_LeftAndMainController';

	private static $menu_title = 'CMSMenuTest_LeftAndMainController';

	private static $menu_priority = 50;
}

class CMSMenuTest_CustomTitle extends LeftAndMain implements TestOnly {

	private static $url_segment = 'CMSMenuTest_CustomTitle';

	private static $menu_priority = 50;

	public static function menu_title($class = null, $localised = false) {
		if($localised) {
			return __CLASS__ . ' (localised)';
		} else {
			return __CLASS__ . ' (unlocalised)';
		}
	}
}
