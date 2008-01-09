<?php

/**
 * @package forms
 * @subpackage core
 */

/**
 * Creates an edit form on a site page.
 * Extends the basic form class to automatically look up, and save to, the data-object referred to
 * by controller->data().
 * @package forms
 * @subpackage core
 * @deprecated I'm not sure if this is in production use?  Is this a legacy of a bygone era?
 */
class EditForm extends Form {
	function __construct($controller, $name, FieldSet $fields) {

	  $this->data = $controller->data();
		
	  $actions = new FieldSet(
		  new FormAction("save", _t('Form.SAVECHANGES', "Save Changes"))
	  );
		
		$sequential = $fields->dataFields();
		
		foreach($sequential as $field) {
			$fieldName = $field->Name();
			// echo "<li>$fieldName";
			$field->setValue($this->data->$fieldName);
		}
		
		parent::__construct($controller, $name, $fields, $actions);
	}
	
	/**
	 * Form handler.  Saves all changed fields to the database, and returns back to the
	 * index action of the given object
	 */
	function save($params) {
		$record = $this->controller->data();

		foreach($this->fields as $field) {
			$fieldName = $field->Name();
			if(isset($params[$fieldName])) {
				$record->$fieldName = $params[$fieldName];
			}
		}
		
		$record->write();
		Director::redirect($this->controller->Link());
	}
}


?>