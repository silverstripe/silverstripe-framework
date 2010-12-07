<?php
class ContentControllerSearchExtensionTest extends SapphireTest {
	
	function testCustomSearchFormClassesToTest() {
		FulltextSearchable::enable('File');
		
		$page = new Page(); 
		$controller = new ContentController($page);
		$form = $controller->SearchForm(); 
		
		if (get_class($form) == 'SearchForm') $this->assertEquals(array('File'), $form->getClassesToSearch());
	}
}