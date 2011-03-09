<?php
/**
 * Single action button.
 * The action buttons are <input type="submit"> tags.
 * 
 * <b>Usage</b>
 * 
 * Upon clicking the button below will redirect the user to doAction under the current controller.
 * 
 * <code>
 * new FormAction (
 *    // doAction has to be a defined controller member
 *    $action = "doAction",
 *    $title = "Submit button"
 * )
 * </code>
 * 
 * <b>Labels</b>
 * 
 * By default, FormAction will use the title as the label for the left margin.  This can look redundant on the form.  If you'd rather have just the button alone with as pictured above try using {@link FormAction_WithoutLabel} instead.
 * 
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
	
	private $buttonContent = null;
	
	/**
	 * Add content inside a button field.
	 */
	function setButtonContent($content) {
		$this->buttonContent = (string) $content;
	}
	
	
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
		$this->addExtraClass($extraClass); 
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
				'name' => $this->action,
				'tabindex' => $this->getTabIndex()
			);
			if($this->isReadonly()) {
				$attributes['disabled'] = 'disabled';
				$attributes['class'] = $attributes['class'] . ' disabled';
			}
			
			return $this->createTag('button', $attributes, $this->buttonContent ? $this->buttonContent : $this->Title());
		} else {
			$attributes = array(
				'class' => 'action' . ($this->extraClass() ? $this->extraClass() : ''),
				'id' => $this->id(),
				'type' => 'submit',
				'name' => $this->action,
				'value' => $this->Title(),
				'tabindex' => $this->getTabIndex()
			);
			if($this->isReadonly()) {
				$attributes['disabled'] = 'disabled';
				$attributes['class'] = $attributes['class'] . ' disabled';
			}
			$attributes['title'] = ($this->description) ? $this->description : $this->Title();
			
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
