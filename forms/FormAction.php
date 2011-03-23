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
	 */
	function __construct($action, $title = "", $form = null, $extraData = null) {
		$this->extraData = $extraData;
		$this->action = "action_$action";
		parent::__construct($this->action, $title, null, $form);
	}

	static function create($action, $title = "", $extraData = null) {
		return new FormAction($action, $title, null, $extraData);
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
	}

	function extraData() {
		return $this->extraData;
	}

	function Field($properties = array()) {
		$properties = array_merge(
			$properties,
			array(
				'Name' => $this->action,
				'Title' => ($this->description) ? $this->description : $this->Title(),
				'UseButtonTag' => $this->useButtonTag
			)
		);
		return $this->customise($properties)->renderWith('FormAction');
	}

	public function Type() {
		return ($this->useButtonTag) ? 'button' : 'submit';
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