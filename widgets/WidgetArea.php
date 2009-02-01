<?php
/**
 * Represents a set of widgets shown on a page.
 * @package sapphire
 * @subpackage widgets
 */
class WidgetArea extends DataObject {
	
	static $db = array();
	
	static $has_one = array();
	
	static $has_many = array(
		"Widgets" => "Widget"
	);
	
	static $many_many = array();
	
	static $belongs_many_many = array();
	
	function forTemplate() {
		return $this->renderWith($this->class); 
	}
}

?>