<?php
/**
 * Base class for widgets.
 * Widgets let CMS authors drag and drop small pieces of functionality into defined areas of their websites.
 * @package sapphire
 * @subpackage widgets
 */
class Widget extends DataObject {
	static $db = array(
		"Sort" => "Int"
	);
	
	static $has_one = array(
		"Parent" => "WidgetArea",
	);
	
	static $has_many = array();
	static $many_many = array();
	static $belongs_many_many = array();
	static $defaults = array();
	
	static $default_sort = "Sort";
	
	static $title = "Widget Title";
	static $cmsTitle = "Name of this widget";
	static $description = "Description of what this widget does.";
	
	function getCMSFields() {
		return new FieldSet();
	}
	
	function WidgetHolder() {
		return $this->renderWith("WidgetHolder");
	}
	
	function Content() {
		return $this->renderWith($this->class);
	}
	
	function Title() {
		return Object::get_static($this->class, 'title');
	}
	
	function CMSTitle() {
		return Object::get_static($this->class, 'cmsTitle');
	}
	
	function Description() {
		return Object::get_static($this->class, 'description');
	}
	
	function DescriptionSegment() {
		return $this->renderWith('WidgetDescription'); 
	}
	
	function EditableSegment() {
		return $this->renderWith('WidgetEditor'); 
	}
	
	function CMSEditor() {
		$output = '';
		
		$fields = $this->getCMSFields();
		foreach($fields as $field) {
			$name = $field->Name();
			$field->setValue($this->getField($name));
			$renderedField = $field->FieldHolder();
			$renderedField = ereg_replace("name=\"([A-Za-z0-9\-_]+)\"", "name=\"Widget[" . $this->ID . "][\\1]\"", $renderedField);
			$renderedField = ereg_replace("id=\"([A-Za-z0-9\-_]+)\"", "id=\"Widget[" . $this->ID . "][\\1]\"", $renderedField);
			$output .= $renderedField;
		}
		return $output;
	}
	
	function ClassName() {
		return $this->class;
	}
	
	function Name() {
		return "Widget[".$this->ID."]";
	}

	function populateFromPostData($data) {
		foreach($data as $name => $value) {
			if($name != "Type") {
				$this->setField($name, $value);
			}
		}
		
		$this->write();
		
		// The field must be written to ensure a unique ID.
		$this->Name = $this->class.$this->ID;
		$this->write();
	}
	
	function FormObjectLink($formName) {
		if(is_numeric($this->ID)) {
			return "WidgetFormProxy/index/$this->ID?executeForm=$formName";
		} else {
			user_error("Attempted to create a form on a widget that hasn't been saved to the database.", E_USER_WARNING);
		}
	}
}

class Widget_Controller extends Controller {
	function editablesegment() {
		$className = $this->urlParams['ID'];
		if(class_exists($className) && is_subclass_of($className, 'Widget')) {
			$obj = new $className();
			return $obj->EditableSegment();
		} else {
			user_error("Bad widget class: $className", E_USER_WARNING);
			return "Bad widget class name given";
		}
	}
}

?>