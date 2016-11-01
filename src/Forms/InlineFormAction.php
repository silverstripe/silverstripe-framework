<?php

namespace SilverStripe\Forms;

use SilverStripe\View\Requirements;

/**
 * Render a button that will submit the form its contained in through ajax.
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 *
 * @see framework/client/dist/js/InlineFormAction.js
 */
class InlineFormAction extends FormField {

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
}
