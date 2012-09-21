<?php
/**
 * Action that clears all fields on a form.
 * Inserts an input tag with type=reset.
 * @package forms
 * @subpackage actions
 */
class ResetFormAction extends FormAction {

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('type' => 'reset')
		);
	}

	public function Type() {
		return 'resetformaction';
	}

}
