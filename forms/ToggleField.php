<?php

/**
 * ReadonlyField with added toggle-capabilities - will preview the first sentence of the contained
 * text-value, and show the full content by a javascript-switch.
 *
 * Caution: Strips HTML-encoding for the preview.
 *
 * @deprecated 3.1 Use custom javascript with a ReadonlyField.
 *
 * @package forms
 * @subpackage fields-dataless
 */
class ToggleField extends ReadonlyField {

	/**
	 * Text shown as a link to see the full content of the field.
	 *
	 * @var string
	 */
	public $labelMore;

	/**
	 * Text shown as a link to see the partial view of the field content.
	 *
	 * @var string
	 */
	public $labelLess;

	/**
	 * Method to use when truncating this field's value (FirstSentence|FirstParagraph).
	 *
	 * @see Text
	 *
	 * @var string
	 */
	public $truncateMethod = 'FirstSentence';

	/**
	 * Number of characters to truncate this field's value to.
	 *
	 * @var null|int
	 */
	public $truncateChars;

	/**
	 * @var null|int
	 */
	public $charNum;

	/**
	 * @var bool
	 */
	public $startClosed;

	/**
	 * @inheritdoc
	 *
	 * @param string $name
	 * @param string $title
	 * @param string $value
	 */
	public function __construct($name, $title = '', $value = '') {
		Deprecation::notice('4.0', 'Use custom javascript with a ReadOnlyField');

		$this->labelMore = _t('ToggleField.MORE', 'more');
		$this->labelLess = _t('ToggleField.LESS', 'less');

		$this->startClosed(true);

		parent::__construct($name, $title, $value);
	}

	/**
	 * @inheritdoc
	 *
	 * @param array $properties
	 *
	 * @return string|HTMLText
     */
	public function Field($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/ToggleField.js');

		if($this->startClosed) {
			$this->addExtraClass('startClosed');
		}

		$valueForInput = '';

		if($this->value) {
			$valueForInput = Convert::raw2att($this->value);
		}

		$rawInput = Convert::html2raw($valueForInput);

		if($this->charNum) {
			$reducedValue = substr($rawInput, 0, $this->charNum);
		} else {
			$reducedValue = DBField::create_field('Text', $rawInput)->{$this->truncateMethod}();
		}

		// only create toggle field if the truncated content is shorter
		if(strlen($reducedValue) < strlen($rawInput)) {
			$content = <<<HTML
			<div class="readonly typography contentLess" style="display: none">
				$reducedValue
				&nbsp;<a href="#" class="triggerMore">$this->labelMore</a>
			</div>
			<div class="readonly typography contentMore">
				$this->value
				&nbsp;<a href="#" class="triggerLess">$this->labelLess</a>
			</div>
			<br />
			<input type="hidden" name="$this->name" value="$valueForInput" />
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
	 * @param bool $bool
	 */
	public function startClosed($bool) {
		if($bool) {
			$this->addExtraClass('startClosed');
		} else {
			$this->removeExtraClass('startClosed');
		}
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
     */
	public function Type() {
		return "toggleField";
	}
}

