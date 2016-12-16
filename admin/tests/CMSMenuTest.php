<?php

namespace SilverStripe\Admin\Tests;

use SilverStripe\Admin\CMSMenu;
use SilverStripe\Admin\CMSMenuItem;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;

class CMSMenuTest extends SapphireTest implements TestOnly
{

    public function testBasicMenuHandling()
    {
        // Clear existing menu
        CMSMenu::clear_menu();
        $menuItems = CMSMenu::get_menu_items();
        $this->assertTrue((empty($menuItems)), 'Menu can be cleared');

        // Add a controller to the menu and check it is as expected
        CMSMenu::add_controller(CMSMenuTest\LeftAndMainController::class);
        $menuItems = CMSMenu::get_menu_items();
        $menuItem = $menuItems['SilverStripe-Admin-Tests-CMSMenuTest-LeftAndMainController'];
        $this->assertInstanceOf(CMSMenuItem::class, $menuItem, 'Controller menu item is of class CMSMenuItem');
        $this->assertContains(
            $menuItem->url,
            CMSMenuTest\LeftAndMainController::singleton()->Link(),
            'Controller menu item has the correct link'
        );
        $this->assertEquals(
            $menuItem->controller,
            CMSMenuTest\LeftAndMainController::class,
            'Controller menu item has the correct controller class'
        );
        $this->assertEquals(
            $menuItem->priority,
            CMSMenuTest\LeftAndMainController::singleton()->stat('menu_priority'),
            'Controller menu item has the correct priority'
        );
        CMSMenu::clear_menu();

        // Add another controller
        CMSMenu::add_controller(CMSMenuTest\CustomTitle::class);
        $menuItems = CMSMenu::get_menu_items();
        $menuItem = $menuItems['SilverStripe-Admin-Tests-CMSMenuTest-CustomTitle'];
        $this->assertInstanceOf(CMSMenuItem::class, $menuItem, 'Controller menu item is of class CMSMenuItem');
        $this->assertEquals(CMSMenuTest\CustomTitle::class . ' (localised)', $menuItem->title);
        CMSMenu::clear_menu();

        // Add a link to the menu
        CMSMenu::add_link('LinkCode', 'link title', 'http://www.example.com');
        $menuItems = CMSMenu::get_menu_items();
        $menuItem = $menuItems['LinkCode'];
        $this->assertInstanceOf(CMSMenuItem::class, $menuItem, 'Link menu item is of class CMSMenuItem');
        $this->assertEquals($menuItem->title, 'link title', 'Link menu item has the correct title');
        $this->assertEquals($menuItem->url, 'http://www.example.com', 'Link menu item has the correct link');
        $this->assertNull($menuItem->controller, 'Link menu item has no controller class');
        $this->assertEquals($menuItem->priority, -1, 'Link menu item has the correct priority');
        CMSMenu::clear_menu();
    }

    public function testRemove()
    {
        CMSMenu::clear_menu();
        CMSMenu::add_menu_item('custom', 'Custom Title', 'custom');
        CMSMenu::add_menu_item('other', 'Other Section', 'other', CMSMenuTest\LeftAndMainController::class);
        $this->assertNotEmpty(CMSMenu::get_menu_items());

        CMSMenu::remove_menu_class(CMSMenuTest\LeftAndMainController::class);
        CMSMenu::remove_menu_item('custom');

        $this->assertEmpty(CMSMenu::get_menu_items());
    }

    public function testLinkWithExternalAttributes()
    {
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

    public function testCmsClassDetection()
    {

        // Get CMS classes and check that:
        //	1.) SecurityAdmin is included
        //	2.) LeftAndMain & ModelAdmin are excluded
        $cmsClasses = CMSMenu::get_cms_classes();
        $this->assertContains(SecurityAdmin::class, $cmsClasses, 'SecurityAdmin included in valid CMS Classes');
        $this->assertNotContains(LeftAndMain::class, $cmsClasses, 'LeftAndMain not included in valid CMS Classes');
        $this->assertNotContains(ModelAdmin::class, $cmsClasses, 'LeftAndMain not included in valid CMS Classes');
    }

    public function testAdvancedMenuHandling()
    {

        // Populate from CMS Classes, check for existance of SecurityAdmin
        CMSMenu::clear_menu();
        CMSMenu::populate_menu();
        $menuItem = CMSMenu::get_menu_item('SilverStripe-Admin-SecurityAdmin');
        $this->assertInstanceOf(CMSMenuItem::class, $menuItem, 'SecurityAdmin menu item exists');
        $this->assertContains($menuItem->url, SecurityAdmin::singleton()->Link(), 'Menu item has the correct link');
        $this->assertEquals(
            SecurityAdmin::class,
            $menuItem->controller,
            'Menu item has the correct controller class'
        );
        $this->assertEquals(
            SecurityAdmin::singleton()->stat('menu_priority'),
            $menuItem->priority,
            'Menu item has the correct priority'
        );

        // Check that menu order is correct by priority
        // Note this will break if populate_menu includes normal links (ie, as not controller)
        $menuItems = CMSMenu::get_menu_items();
        $priority = 9999; // ok, *could* be set larger, but shouldn't need to be!
        foreach ($menuItems as $menuItem) {
            $this->assertEquals(
                $menuItem->priority,
                singleton($menuItem->controller)->stat('menu_priority'),
                "Menu item $menuItem->title has the correct priority"
            );
            $this->assertLessThanOrEqual($priority, $menuItem->priority, 'Menu item is of lower or equal priority');
        }
    }
}
