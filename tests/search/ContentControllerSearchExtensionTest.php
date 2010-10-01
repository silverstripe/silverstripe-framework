<?php
class ContentControllerSearchExtensionTest extends SapphireTest {
	
	function testCustomSearchFormClassesToTest() {
		FulltextSearchable::enable('File');
		
		$page = new Page(); 
		$controller = new ContentController($page);
		$form = $controller->SearchForm(); 
		
		$this->assertEquals(array('File'), $form->getClassesToSearch());
	}
}