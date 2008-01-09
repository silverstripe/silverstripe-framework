<?php

/**
 * @package forms
 * @subpackage fields-dataless
 */

/**
 * A {@link LabelField} that lets you give it a name; makes it easier to delete ;)
 * @package forms
 * @subpackage fields-dataless
 */
class NamedLabelField extends LabelField {
	protected $className;
	protected $allowHTML;
	protected $labelName;
	
	/**
	 * Create a new label.
	 * @param title The label itslef
	 * @param class An HTML class to apply to the label.
	 */
	function __construct($name, $title, $className = "", $allowHTML = false, $form = null) {
		$this->labelName = $name;
		parent::__construct($title, $className, $allowHTML, $form);
	}
	
	/**
	 * Returns a label containing the title, and an HTML class if given.
	 */
	function Field() {
		if($this->className) $classClause = " class=\"$this->className\"";
		return "<label id=\"" . $this->id() . "\"$classClause>" . ($this->allowHTML ? $this->title : htmlentities($this->title)) . "</label>";
	}
	
	function id() {
		return parent::id() . $this->labelName;
	}
}
?>