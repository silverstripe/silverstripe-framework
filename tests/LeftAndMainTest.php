<?php
class LeftAndMainTest extends SapphireTest {
	
	public function testMainMenuSpecification() {
		
		// clear existing menu
		LeftAndMain::clear_menu();
		$allMenuItems = LeftAndMain::get_menu_items();
		$this->assertTrue((empty($allMenuItems)), 'Menu can be cleared');

		// populate defaults and check for "content" entry
		LeftAndMain::populate_default_menu();
		$contentMenuItem = LeftAndMain::get_menu_item('content');
		$this->assertTrue((is_array($contentMenuItem)) && !empty($contentMenuItem), '"Content" menu entry exists');
		
		// try duplicate adding
		$duplicateAddSuccess = LeftAndMain::add_menu_item(
			"content",
			_t('LeftAndMain.SITECONTENT',"Site Content",PR_HIGH,"Menu title"),
			"admin/",
			"CMSMain"
		);
		$this->assertTrue(($duplicateAddSuccess === false), '"Content" menu entry can\'t be readded through add_menu_item()');

		// try replacing
		$replaceSuccess = LeftAndMain::replace_menu_item(
			"content",
			'My Custom Title',
			"mycustomroute",
			"MyCMSMain"
		);
		$replacedMenuItem = LeftAndMain::get_menu_item("content");
		$this->assertEquals($replacedMenuItem['title'],'My Custom Title'); 
		$this->assertEquals($replacedMenuItem['url'],'mycustomroute'); 
		$this->assertEquals($replacedMenuItem['controllerClass'],'MyCMSMain');
		
		// try removing
		LeftAndMain::remove_menu_item("content");
		$removedMenuItem = LeftAndMain::get_menu_item("content");
		$this->assertTrue(($removedMenuItem === false), 'Menu item can be removed');
		
		// restore default menu
		LeftAndMain::populate_default_menu();
	}
	
}
?>