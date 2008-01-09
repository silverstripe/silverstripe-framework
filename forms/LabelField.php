<?php

/**
 * @package forms
 * @subpackage fields-dataless
 */

/**
 * Simple label tag.
 * This can be used to add extra text in your forms.
 * @package forms
 * @subpackage fields-dataless
 */
class LabelField extends DatalessField {
	protected $className;
	protected $allowHTML;
	
	/**
	 * Create a new label.
	 * @param title The label itslef
	 * @param class An HTML class to apply to the label.
	 */
	function __construct($title, $className = "", $allowHTML = false, $form = null) {
		$this->className = $className;
		$this->allowHTML = $allowHTML;

		parent::__construct(null, $title, null, $form);
	}
	
	/**
	 * Returns a label containing the title, and an HTML class if given.
	 */
	function Field() {
		$classClause = $this->className ? " class=\"$this->className\"" : '';
		return "<label$classClause>" . ($this->allowHTML ? $this->title : htmlentities($this->title)) . "</label>";
	}
}
?>