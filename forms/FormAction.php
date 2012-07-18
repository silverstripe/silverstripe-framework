<?php
/**
 * The action buttons are <input type="submit"> as well as <button> tags.
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
 * @package forms
 * @subpackage actions
 */
class FormAction extends FormField {

	protected $action;
	
	/**
	 * Enables the use of <button> instead of <input>
	 * in {@link Field()} - for more customizeable styling.
	 * 
	 * @var boolean $useButtonTag
	 */
	public $useButtonTag = false;
	
	protected $buttonContent = null;
	
	/**
	 * Create a new action button.
	 *
	 * @param action The method to call when the button is clicked
	 * @param title The label on the button
	 * @param form The parent form, auto-set when the field is placed inside a form 
	 */
	function __construct($action, $title = "", $form = null) {
		$this->action = "action_$action";
		
		parent::__construct($this->action, $title, null, $form);
	}

	function actionName() {
		return substr($this->name, 7);
	}
	
	/**
	 * Set the full action name, including action_
	 * This provides an opportunity to replace it with something else
	 */
	function setFullAction($fullAction) {
		$this->action = $fullAction;
		return $this;
	}

	function Field($properties = array()) {
		$properties = array_merge(
			$properties,
			array(
				'Name' => $this->action,
				'Title' => ($this->description && !$this->useButtonTag) ? $this->description : $this->Title(),
				'UseButtonTag' => $this->useButtonTag
			)
		);
		
		return parent::Field($properties);
	}
	
	function FieldHolder($properties = array()) {
		return $this->Field($properties);
	}

	public function Type() {
		return 'action';
	}

	function getAttributes() {
		$type = (isset($this->attributes['src'])) ? 'image' : 'submit';
		$type = ($this->useButtonTag) ? null : $type;
		
		return array_merge(
			parent::getAttributes(),
			array(
				'disabled' => ($this->isReadonly() || $this->isDisabled()),
				'value' => $this->Title(),
				'type' => $type,
				'title' => ($this->useButtonTag) ? $this->description : null,
			)
		);
	}

	/**
	 * Add content inside a button field.
	 */
	function setButtonContent($content) {
		$this->buttonContent = (string) $content;
		return $this;
	}

	/**
	 * @return String
	 */
	function getButtonContent() {
		return $this->buttonContent;
	}

	/**
	 * @param Boolean
	 */
	public function setUseButtonTag($bool) {
		$this->useButtonTag = $bool;
		return $this;
	}

	/**
	 * @return Boolean
	 */
	public function getUseButtonTag() {
		return $this->useButtonTag;
	}

	function extraClass() {
		return 'action ' . parent::extraClass();
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

}
