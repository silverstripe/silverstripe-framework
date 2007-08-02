<?php

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