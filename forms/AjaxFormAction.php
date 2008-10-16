<?php
/**
 * Action button with Ajax/JavaScript overloading.
 * @deprecated 2.3
 * @package forms
 * @subpackage actions
 */
class AjaxFormAction extends FormAction {
	protected $ajaxAction;
	
	/**
	 * Create a new action button.
	 * @param action The method to call when the button is clicked
	 * @param title The label on the button
	 * @param confirmation The message to display in the confirmation box?
	 * @param form The parent form, auto-set when the field is placed inside a form 
	 */
	function __construct($action, $title = "", $ajaxAction = null, $form = null) {
		$this->ajaxAction = $ajaxAction ? $ajaxAction : $action;
		parent::__construct($action, $title, $form);
	}
	
	function Field() {
		return $this->createTag('input', array(
			'class' => "ajaxAction-$this->ajaxAction action",
			'id' => $this->id(),
			'type' => 'submit',
			'value' => $this->title,
			'tabindex' => $this->getTabIndex()
		));
	}
	
	function Title() { 
		return false; 
	}
}

?>