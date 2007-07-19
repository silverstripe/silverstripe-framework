<?php
/**
 * Field that generates a heading tag.
 * This can be used to add extra text in your forms.
 */
class HeaderField extends DatalessField {
	protected $headingLevel, $allowHTML;
	
	function __construct($title, $headingLevel = 2, $allowHTML = false, $form = null) {
		$this->headingLevel = $headingLevel;
		$this->allowHTML = $allowHTML;

		parent::__construct(null, $title, null, $form);
	}
	function Field() {
		if($this->allowHTML) $XML_title = $this->title;
		else $XML_title = Convert::raw2xml($this->title);
		
		return "<h$this->headingLevel>$XML_title</h$this->headingLevel>";
	}
}
?>