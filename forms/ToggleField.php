<?php
/**
 * ReadonlyField with added toggle-capabilities - will preview the first sentence of the contained text-value,
 * and show the full content by a javascript-switch.
 * 
 * Caution: Strips HTML-encoding for the preview.
 * @package forms
 * @subpackage fields-dataless
 */

class ToggleField extends ReadonlyField {

	/**
	 * @var $labelMore string Text shown as a link to see the full content of the field
	 */
	public $labelMore;
	
	/**
	 * @var $labelLess string Text shown as a link to see the partial view of the field content 
	 */
	public $labelLess;
	
	/**
	 * @see Text
	 * @var $truncateMethod string (FirstSentence|FirstParagraph)
	 */
	public $truncateMethod = 'FirstSentence';
	
	/**
	 * @var $truncateChars int Number of chars to preview (optional). 
	 * 	Truncating will be applied with $truncateMethod by default. 
	 */
	public $truncateChars;
	
	/**
	 * @param name The field name
	 * @param title The field title
	 * @param value The current value
	 */
	function __construct($name, $title = "", $value = "") {
		$this->labelMore = _t('ToggleField.MORE', 'more');
		$this->labelLess = _t('ToggleField.LESS', 'less');
		
		$this->startClosed(true);
		
		parent::__construct($name, $title, $value);
	}

	function Field($properties = array()) {
		$content = '';
		
		Requirements::javascript(FRAMEWORK_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(FRAMEWORK_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(FRAMEWORK_DIR . "/javascript/ToggleField.js");
		
		if($this->startClosed) $this->addExtraClass('startClosed');
		
		$valforInput = $this->value ? Convert::raw2att($this->value) : "";
		$rawInput = Convert::html2raw($valforInput);
		
		if($this->charNum) $reducedVal = substr($rawInput,0,$this->charNum);
		else $reducedVal = DBField::create_field('Text',$rawInput)->{$this->truncateMethod}();
		
		// only create togglefield if the truncated content is shorter
		if(strlen($reducedVal) < strlen($rawInput)) {
			$content = <<<HTML
			<div class="readonly typography contentLess" style="display: none">
				$reducedVal
				&nbsp;<a href="#" class="triggerMore">$this->labelMore</a>
			</div>
			<div class="readonly typography contentMore">
				$this->value
				&nbsp;<a href="#" class="triggerLess">$this->labelLess</a>
			</div>	
			<br />
			<input type="hidden" name="$this->name" value="$valforInput" />
HTML;
		} else {
			$this->dontEscape = true;
			$content = parent::Field();
		}
		
		return $content;
	}
	
	/**
	 * Determines if the field should render open or closed by default.
	 * 
	 * @param boolean
	 */
	public function startClosed($bool) {
		($bool) ? $this->addExtraClass('startClosed') : $this->removeExtraClass('startClosed');
	}
	
	function Type() {
		return "toggleField";
	}
}

