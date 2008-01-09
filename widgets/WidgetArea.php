<?php

/**
 * @package sapphire
 * @subpackage widgets
 */

/**
 * Represents a set of widgets shown on a page.
 * @package sapphire
 * @subpackage widgets
 */
class WidgetArea extends DataObject {
	static $db = array();
	
	static $has_many = array(
		"Widgets" => "Widget"
	);
	
	function forTemplate() {
		return $this->renderWith("WidgetArea");
	}
}

?>