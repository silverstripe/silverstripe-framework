<?php
/**
 * Action button with confirmation text.
 * These button are useful for things like delete buttons.
 *
 * @deprecated 2.3 Instead of using ConfirmedFormAction, just
 * apply a simple javascript event handler to your standard
 * FormAction form fields.
 *
 * @package forms
 * @subpackage actions
 */
class ConfirmedFormAction extends FormAction {
	protected $confirmation;
	
	/**
	 * Create a new action button.
	 * @param action The method to call when the button is clicked
	 * @param title The label on the button
	 * @param confirmation The message to display in the confirmation box
	 * @param form The parent form, auto-set when the field is placed inside a form 
	 */
	function __construct($action, $title = "", $confirmation = null, $form = null) {
		if($confirmation) {
			$this->confirmation = $confirmation;
		} else {
			$this->confirmation = _t('ConfirmedFormAction.CONFIRMATION', "Are you sure?", PR_MEDIUM, 'Confirmation popup before executing the form action');
		}
		
		parent::__construct($action, $title, $form);
	}
	
	function Field() {
		$attributes = array(
			'type' => 'submit',
			'class' => ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => $this->attrTitle(),
			'tabindex' => $this->getTabIndex(),
			'onclick' => "return confirm('$this->confirmation');"
		);
		
		return $this->createTag('input', $attributes);
	}
}


?>