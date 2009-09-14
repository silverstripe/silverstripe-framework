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
	
	/**
	 * Used in template instead of {@link Widgets()}
	 * to wrap each widget in its controller, making
	 * it easier to access and process form logic
	 * and actions stored in {@link Widget_Controller}.
	 * 
	 * @return DataObjectSet Collection of {@link Widget_Controller}
	 */
	function WidgetControllers() {
		$controllers = new DataObjectSet();
		foreach($this->Widgets() as $widget) {
			// find controller
			$controllerClass = '';
			foreach(array_reverse(ClassInfo::ancestry($widget->class)) as $widgetClass) {
				$controllerClass = "{$widgetClass}_Controller";
				if(class_exists($controllerClass)) break;
			}
			$controller = new $controllerClass($widget);
			$controller->init();
			$controllers->push($controller);
		}
		
		return $controllers;
	}
	
	function forTemplate() {
		return $this->renderWith($this->class); 
	}
}

?>