<?php

/**
 * @package forms
 * @subpackage actions
 */

/**
 * Single action button.
 * The action buttons are <input type="submit"> tags.
 * @package forms
 * @subpackage actions
 */
class FormAction extends FormField {
	protected $extraData;
	
	/**
	 * Create a new action button.
	 * @param action The method to call when the button is clicked
	 * @param title The label on the button
	 * @param form The parent form, auto-set when the field is placed inside a form 
	 * @param extraData A piece of extra data that can be extracted with $this->extraData.  Useful for
	 *                  calling $form->buttonClicked()->extraData()
	 * @param extraClass A CSS class to apply to the button in addition to 'action'
	 */
	function __construct($action, $title = "", $form = null, $extraData = null, $extraClass = '') {
		$this->extraData = $extraData;
		$this->extraClass = ' '.$extraClass;
		parent::__construct("action_$action", $title, null, $form);
	}
	static function create($action, $title = "", $extraData = null, $extraClass = null) {
		return new FormAction($action, $title, null, $extraData, $extraClass);
	}
	
	function actionName() {
		return substr($this->name,7);
	}

	function extraData() {
		return $this->extraData;
	}
	
	function Field() {
		$titleAttr = $this->description ? "title=\"" . Convert::raw2att($this->description) . "\"" : '';
		return "<input class=\"action " . $this->extraClass() . "\" id=\"" . $this->id() . "\" type=\"submit\" name=\"{$this->name}\" value=\"" . $this->attrTitle() . "\" $titleAttr />\n";
	}
	
	/**
	 * Does not transform to readonly by purpose.
	 * Globally disabled buttons would break the CMS.
	 */
	function performReadonlyTransformation() {
		return $this;
	}
	
	function readonlyField() {
		return $this;
	}
}

/**
 * @package forms
 * @subpackage actions
 */
class FormAction_WithoutLabel extends FormAction {
	function Title(){
		return null;
	}
}
?>