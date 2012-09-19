<?php
/**
 * This is a form decorator that lets you place a form inside another form.
 * The actions will be appropriately rewritten so that the nested form gets called, rather than the parent form.
 * 
 * @package framework
 * @subpackage forms
 */
class NestedForm extends ViewableData {
	protected $form;

	/**
	 * Represent the given form in a tabular style
	 * @param form The form to decorate.
	 */
	public function __construct(Form $form) {
		$this->form = $form;
		$this->failover = $form;
		parent::__construct();
	}
	
	public function Actions() {
		$actions = $this->form->Actions();
		foreach($actions as $action) {
			$action->setFullAction('action_' . $action->actionName() 
				.'?formController=' . str_replace(array('?','.'), array('&','%2e'), $this->form->FormAction()) );
		}
		return $actions;
	}
}
