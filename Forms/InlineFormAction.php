<?php

namespace SilverStripe\Forms;

use SilverStripe\View\Requirements;

/**
 * Render a button that will submit the form its contained in through ajax.
 * If you want to add custom behaviour, please set {@link includeDefaultJS()} to FALSE
 *
 * @see framework/client/dist/js/InlineFormAction.js
 */
class InlineFormAction extends FormField {

	protected $includeDefaultJS = true;

	/**
	 * Create a new action button.
	 *
	 * @param string $action The method to call when the button is clicked
	 * @param string $title The label on the button
	 * @param string $extraClass A CSS class to apply to the button in addition to 'action'
	 */
	public function __construct($action, $title = "", $extraClass = '') {
		$this->extraClass = ' '.$extraClass;
		parent::__construct($action, $title);
	}

	public function performReadonlyTransformation() {
		return $this->castedCopy('SilverStripe\\Forms\\InlineFormAction_ReadOnly');
	}

	/**
	 * @param array $properties
	 * @return string
	 */
	public function Field($properties = array()) {
		if($this->includeDefaultJS) {
			Requirements::javascriptTemplate(
				FRAMEWORK_DIR . '/client/dist/js/InlineFormAction.js',
				array('ID'=>$this->ID())
			);
		}

		return FormField::create_tag('input', array(
				'type' => 'submit',
				'name' => sprintf('action_%s', $this->getName()),
		        'value' => $this->title,
		        'id' => $this->ID(),
		        'class' => sprintf('action%s', $this->extraClass),
		));
	}

	public function Title() {
		return false;
	}

	/**
	 * Optionally disable the default javascript include (framework/client/dist/js/InlineFormAction.js),
	 * which routes to an "admin-custom"-URL.
	 *
	 * @param $bool boolean
	 */
	public function includeDefaultJS($bool) {
		$this->includeDefaultJS = (bool)$bool;
	}
}
