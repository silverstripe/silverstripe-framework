<?php

/**
 * @package forms
 * @subpackage fields-dataless
 */

/**
 * Field that generates a heading tag.
 * This can be used to add extra text in your forms.
 * @package forms
 * @subpackage fields-dataless
 */
class HeaderField extends DatalessField {
	protected $headingLevel, $allowHTML;
	
	function __construct($title, $headingLevel = 2, $allowHTML = false, $form = null) {
		$this->headingLevel = $headingLevel;
		$this->allowHTML = $allowHTML;

		parent::__construct(null, $title, null, $form);
	}
	function Field() {
		$XML_title = ($this->allowHTML) ? $this->title : Convert::raw2xml($this->title);
		
		// extraclass
		$XML_class = ($this->extraClass()) ? " class=\"{$this->extraClass()}\"" : '';  
		
		return "<h{$this->headingLevel}{$XML_class}>$XML_title</h$this->headingLevel>";
	}
}
?>