<?php
/**
 * Single action button.
 * The action buttons are <input type="submit"> tags.
 * @package forms
 * @subpackage actions
 */
class FormAction extends FormField {

	protected $extraData;

	protected $action;
	
	/**
	 * Enables the use of <button> instead of <input>
	 * in {@link Field()} - for more customizeable styling.
	 * 
	 * @var boolean $useButtonTag
	 */
	public $useButtonTag = false;
	
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
		$this->action = "action_$action";
		parent::__construct($this->action, $title, null, $form);
	}
	
	static function create($action, $title = "", $extraData = null, $extraClass = null) {
		return new FormAction($action, $title, null, $extraData, $extraClass);
	}
	
	function actionName() {
		return substr($this->name,7);
	}
	
	/**
	 * Set the full action name, including action_
	 * This provides an opportunity to replace it with something else
	 */
	function setFullAction($fullAction) {
		$this->action = $fullAction;
	}

	function extraData() {
		return $this->extraData;
	}
	
	/**
	 * Create a submit input, or button tag
	 * using {@link FormField->createTag()} functionality.
	 * 
	 * @return HTML code for the input OR button element
	 */
	function Field() {
		if($this->useButtonTag) {
			$attributes = array(
				'class' => 'action' . ($this->extraClass() ? $this->extraClass() : ''),
				'id' => $this->id(),
				'type' => 'submit',
				'name' => $this->action
			);
			if($this->isReadonly()) {
				$attributes['disabled'] = 'disabled';
				$attributes['class'] = $attributes['class'] . ' disabled';
			}
			
			return $this->createTag('button', $attributes, $this->attrTitle());
		} else {
			$attributes = array(
				'class' => 'action' . ($this->extraClass() ? $this->extraClass() : ''),
				'id' => $this->id(),
				'type' => 'submit',
				'name' => $this->action,
				'value' => ($this->dontEscape) ? $this->Title() : $this->attrTitle()
			);
			if($this->isReadonly()) {
				$attributes['disabled'] = 'disabled';
				$attributes['class'] = $attributes['class'] . ' disabled';
			}
			$attributes['title'] = ($this->description) ? $this->description : ($this->dontEscape) ? $this->Title() : $this->attrTitle();
			
			return $this->createTag('input', $attributes);
		}
	}
	
	/**
	 * Does not transform to readonly by purpose.
	 * Globally disabled buttons would break the CMS.
	 */
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
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