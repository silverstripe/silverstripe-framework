<?php
/**
 * This is a form decorator (a class that wraps around a form) providing us with some functions
 * to display it in a Tabular style.
 * @package forms
 * @subpackage transformations
 */
class TabularStyle extends ViewableData {
	protected $form;

	/**
	 * Represent the given form in a tabular style
	 * @param form The form to decorate.
	 */
	function __construct($form) {
		$this->form = $form;
		$this->failover = $form;
		parent::__construct();
	}
	
	/**
	 * Return a representation of this form as a table row
	 */
	function AsTableRow() {
		return "<tr class=\"addrow\">{$this->CellFields()}<td class=\"actions\">{$this->CellActions()}</td></tr>";
	}
	
	function CellFields() {
		$result = "";
		$hiddenFields = '';
		foreach($this->form->Fields() as $field) {
			if(!$field->is_a('HiddenField')) {
				$result .= "<td>" . $field->Field() . "</td>";
			} else {
				$hiddenFields .= $field->Field();
			}
		}
		
		// Add hidden fields in the last cell
		$result = substr($result,0,-5) . $hiddenFields . substr($result,-5);
		
		return $result;
	}

	function CellActions() {
		$actions = "";
		foreach($this->form->Actions() as $action) {
			$actions .= $action->Field();
		}
		return $actions;
	}
	
	

	/**
	 * This is the 'wrapper' aspect of the code
	 */
	function __call($func, $args) {
		return call_user_func_array(array(&$this->form, $func), $args);
	}
	function __get($field) {
		return $this->form->$field;
	}
	function __set($field, $val) {
		$this->form->$field = $val;
	}
}
