<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class WidgetControllerTestPage extends Page implements TestOnly {
	static $has_one = array(
		'WidgetControllerTestSidebar' => 'WidgetArea'
	);
}

/**
 * @package sapphire
 * @subpackage tests
 */
class WidgetControllerTestPage_Controller extends Page_Controller implements TestOnly {
	
	/**
	 * Template selection doesnt work in test folders,
	 * so we enforce a template name.
	 */
	function getViewer($action) {
		$templates = array('WidgetControllerTestPage');
		
		return new SSViewer($templates);
	}
}