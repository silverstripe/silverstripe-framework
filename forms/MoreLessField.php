<?php

/**
 * A field that shows only a part of its contents.
 * Using the 'more' and 'less' links you can switch to the complete or to the partial text, respectively
 */

class MoreLessField extends ReadonlyField {

	protected $moreText;
	protected $lessText;
	protected $charNum;
	/**
	 * Creates a new More/Less field.
	 * @param name The field name
	 * @param title The field title
	 * @param value The current value
	 * @param moreText Text shown as a link to see the full content of the field
	 * @param lessText Text shown as a link to see the partial view of the field content
	 * @param chars Number of chars to preview. If zero it'll show the first line or sentence.
	 */
	function __construct($name, $title = "", $value = "", $moreText = 'more', $lessText = 'less', $chars = 0) {
		$this->moreText = $moreText;
		$this->lessText = $lessText;
		$this->charNum = $chars;
		parent::__construct($name, $title, $value);
	}

	function Field() {
		$valforInput = $this->value ? Convert::raw2att($this->value) : "";
		$rawInput = Convert::html2raw($valforInput);
		if ($this->charNum) $reducedVal = substr($rawInput,0,$this->charNum);
		else $reducedVal = ereg_replace('([^\.]\.)[[:space:]].*','\\1',$rawInput);
		if (strlen($reducedVal) < strlen($rawInput)) {
			return <<<HTML
		<div class="readonly typography" id="{$this->id()}_reduced" style="display: inline;">$reducedVal
			<a onclick="\$('{$this->id()}_reduced').style.display='none'; \$('{$this->id()}').style.display='inline'; return false;" href="#"> $this->moreText</a>
		</div>
		<div class="readonly typography" id="{$this->id()}" style="display: none;">$this->value
			<a onclick="\$('{$this->id()}').style.display='none'; \$('{$this->id()}_reduced').style.display='inline'; return false;" href="#"> $this->lessText</a>
		</div>	
		<br /><input type="hidden" name="$this->name" value="$valforInput" />
HTML;
		} else {
			$this->dontEscape = true;
			return parent::Field();
		}
	}
	
	function setMoreText($moreText) {
		$this->moreText = $moreText;
	}

	function setLessText($lessText) {
		$this->lessText = $lessText;
	}

}

?>