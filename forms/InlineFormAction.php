<?php
/**
 * Render a button that will submit the form its contained in through ajax.
 * If you want to add custom behaviour, please set {@link includeDefaultJS()} to FALSE
 *
 * @see framework/javascript/InlineFormAction.js
 *
 * @package forms
 * @subpackage actions
 */
class InlineFormAction extends FormField {

	protected $includeDefaultJS = true;

	/**
	 * Create a new action button.
	 * @param action The method to call when the button is clicked
	 * @param title The label on the button
	 * @param extraClass A CSS class to apply to the button in addition to 'action'
	 */
	public function __construct($action, $title = "", $extraClass = '') {
		$this->extraClass = ' '.$extraClass;
		parent::__construct($action, $title);
	}

	public function performReadonlyTransformation() {
		return $this->castedCopy('InlineFormAction_ReadOnly');
	}

	/**
	 * @param array $properties
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
		if($this->includeDefaultJS) {
			Requirements::javascriptTemplate(FRAMEWORK_DIR . '/javascript/InlineFormAction.js',
				array('ID'=>$this->id()));
		}

		return DBField::create_field(
			'HTMLText',
			FormField::create_tag('input', array(
				'type' => 'submit',
				'name' => sprintf('action_%s', $this->getName()),
		        'value' => $this->title,
		        'id' => $this->ID(),
		        'class' => sprintf('action%s', $this->extraClass),
			))
		);
	}

	public function Title() {
		return false;
	}

	/**
	 * Optionally disable the default javascript include (framework/javascript/InlineFormAction.js),
	 * which routes to an "admin-custom"-URL.
	 *
	 * @param $bool boolean
	 */
	public function includeDefaultJS($bool) {
		$this->includeDefaultJS = (bool)$bool;
	}
}

/**
 * Readonly version of {@link InlineFormAction}.
 * @package forms
 * @subpackage actions
 */
class InlineFormAction_ReadOnly extends FormField {

	protected $readonly = true;

	/**
	 * @param array $properties
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
		return DBField::create_field('HTMLText',
			FormField::create_tag('input', array(
				'type' => 'submit',
	            'name' => sprintf('action_%s', $this->name),
	            'value' => $this->title,
	            'id' => $this->id(),
				'disabled' => 'disabled',
	            'class' => 'action disabled ' . $this->extraClass,
			))
		);
	}

	public function Title() {
		return false;
	}
}
