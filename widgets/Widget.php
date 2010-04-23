<?php
/**
 * Widgets let CMS authors drag and drop small pieces of functionality into 
 * defined areas of their websites.
 * 
 * ## Forms
 * You can use forms in widgets by implementing a {@link Widget_Controller}.
 * See {@link Widget_Controller} for more information.
 * 
 * @package sapphire
 * @subpackage widgets
 */
class Widget extends DataObject {
	static $db = array(
		"Sort" => "Int",
		"Enabled" => "Boolean"
	);
	
	static $defaults = array(
		'Enabled' => true
	);
	
	static $has_one = array(
		"Parent" => "WidgetArea",
	);
	
	static $has_many = array();
	static $many_many = array();
	static $belongs_many_many = array();
	
	static $default_sort = "\"Sort\"";
	
	static $title = "Widget Title";
	static $cmsTitle = "Name of this widget";
	static $description = "Description of what this widget does.";
	
	function getCMSFields() {
		$fields = new FieldSet();
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}
	
	/**
	 * Note: Overloaded in {@link Widget_Controller}.
	 * 
	 * @return string HTML
	 */
	function WidgetHolder() {
		return $this->renderWith("WidgetHolder");
	}
	
	/**
	 * Renders the widget content in a custom template with the same name as the current class.
	 * This should be the main point of output customization.
	 * 
	 * Invoked from within WidgetHolder.ss, which contains
	 * the "framing" around the custom content, like a title.
	 * 
	 * Note: Overloaded in {@link Widget_Controller}.
	 * 
	 * @return string HTML
	 */
	function Content() {
		return $this->renderWith(array_reverse(ClassInfo::ancestry($this->class)));
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
	
	/**
	 * @see Widget_Controller->editablesegment()
	 */
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
	
}

/**
 * Optional controller for every widget which has its own logic,
 * e.g. in forms. It always handles a single widget, usually passed
 * in as a database identifier through the controller URL.
 * Needs to be constructed as a nested controller
 * within a {@link ContentController}.
 * 
 * ## Forms
 * You can add forms like in any other sapphire controller.
 * If you need access to the widget from within a form,
 * you can use `$this->controller->getWidget()` inside the form logic.
 * Note: Widget controllers currently only work on {@link Page} objects,
 * because the logic is implemented in {@link ContentController->handleWidget()}.
 * Copy this logic and the URL rules to enable it for other controllers.
 * 
 * @package sapphire
 * @subpackage widgets
 */
class Widget_Controller extends Controller {
	
	/**
	 * @var Widget
	 */
	protected $widget;
	
	function __construct($widget = null) {
		// TODO This shouldn't be optional, is only necessary for editablesegment()
		if($widget) {
			$this->widget = $widget;
			$this->failover = $widget;
		}
		
		parent::__construct();
	}
	
	public function Link($action = null) {
		return Controller::curr()->Link (
			Controller::join_links('widget', ($this->widget ? $this->widget->ID : null), $action)
		);
	}
	
	/**
	 * @return Widget
	 */
	function getWidget() {
		return $this->widget;
	}
	
	/**
	 * Overloaded from {@link Widget->Content()}
	 * to allow for controller/form linking.
	 * 
	 * @return string HTML
	 */
	function Content() {
		return $this->renderWith(array_reverse(ClassInfo::ancestry($this->widget->class)));
	}
	
	/**
	 * Overloaded from {@link Widget->WidgetHolder()}
	 * to allow for controller/form linking.
	 * 
	 * @return string HTML
	 */
	function WidgetHolder() {
		return $this->renderWith("WidgetHolder");
	}
	
	/**
	 * Uses the `WidgetEditor.ss` template and {@link Widget->editablesegment()}
	 * to render a administrator-view of the widget. It is assumed that this
	 * view contains form elements which are submitted and saved through {@link WidgetAreaEditor}
	 * within the CMS interface.
	 * 
	 * @return string HTML
	 */
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

/**
 * @package sapphire
 * @subpackage widgets
 */
class Widget_TreeDropdownField extends TreeDropdownField {
	function FieldHolder() {}
	function Field() {}
}

?>