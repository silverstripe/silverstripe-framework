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

	/**
	 * Action name, normally prefixed with 'action_'
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * Enables the use of <button> instead of <input>
	 * in {@link Field()} - for more customizeable styling.
	 *
	 * @var boolean
	 */
	public $useButtonTag = false;

	/**
	 * Literal button content, used when useButtonTag is true.
	 *
	 * @var string
	 */
	protected $buttonContent = null;

	/**
	 * Create a new action button.
	 *
	 * @param string $action The method to call when the button is clicked
	 * @param string $title The label on the button. This should be plain text, not escaped as HTML.
	 * @param Form form The parent form, auto-set when the field is placed inside a form
	 */
	public function __construct($action, $title = "", $form = null) {
		$this->action = "action_$action";
		$this->setForm($form);

		parent::__construct($this->action, $title);
	}

	/**
	 * Get the action name
	 *
	 * @return string
	 */
	public function actionName() {
		return substr($this->name, 7);
	}

	/**
	 * Set the full action name, including action_
	 * This provides an opportunity to replace it with something else
	 *
	 * @param string $fullAction
	 * @return $this
	 */
	public function setFullAction($fullAction) {
		$this->action = $fullAction;
		return $this;
	}

	/**
	 * @param array $properties
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
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

	/**
	 * @param array $properties
	 * @return HTMLText
	 */
	public function FieldHolder($properties = array()) {
		return $this->Field($properties);
	}

	public function Type() {
		return 'action';
	}

	public function Title() {
		$title = parent::Title();

		// Remove this method override in 4.0
		$decoded = Convert::xml2raw($title);
		if($title && $decoded !== $title) {
			Deprecation::notice(
				'4.0',
				'The FormAction title field should not be html encoded. Use buttonContent to set custom html instead'
			);
			return $decoded;
		}

		return $title;
	}

	public function getAttributes() {
		$type = (isset($this->attributes['src'])) ? 'image' : 'submit';

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
	 *
	 * @param string $content
	 * @return $this
	 */
	public function setButtonContent($content) {
		$this->buttonContent = (string) $content;
		return $this;
	}

	/**
	 * Gets the content inside the button field
	 *
	 * @return string
	 */
	public function getButtonContent() {
		return $this->buttonContent;
	}

	/**
	 * Enable or disable the rendering of this action as a <button />
	 *
	 * @param boolean
	 * @return $this
	 */
	public function setUseButtonTag($bool) {
		$this->useButtonTag = $bool;
		return $this;
	}

	/**
	 * Determine if this action is rendered as a <button />
	 *
	 * @return boolean
	 */
	public function getUseButtonTag() {
		return $this->useButtonTag;
	}

	/**
	 * Does not transform to readonly by purpose.
	 * Globally disabled buttons would break the CMS.
	 */
	public function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}

}
