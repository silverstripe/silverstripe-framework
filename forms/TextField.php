<?php
/**
 * Text input field.
 *
 * @package forms
 * @subpackage fields-basic
 */
class TextField extends FormField {

	/**
	 * @var int
	 */
	protected $maxLength, $placeholder;
	
	/**
	 * Returns an input field, class="text" and type="text" with an optional maxlength
	 */
	public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null) {
		$this->maxLength = $maxLength;
		
		parent::__construct($name, $title, $value, $form);
	}
	
	/**
	 * @param int $length
	 */
	public function setMaxLength($length) {
		$this->maxLength = $length;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getMaxLength() {
		return $this->maxLength;
	}

	/**
	 * Provide placeholder text for this field.
	 * 
	 * @param string $placeholder
	 */
	public function setPlaceholder($placeholder) {
		$this->placeholder = $placeholder;
		
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPlaceholder() {
		return $this->placeholder;
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'maxlength' => $this->getMaxLength(),
				'size' => ($this->getMaxLength()) ? min($this->getMaxLength(), 30) : null,
				'placeholder' => $this->getPlaceholder(),
			)
		);
	}

	public function InternallyLabelledField() {
		if(!$this->value) $this->value = $this->Title();
		return $this->Field();
	}
	
}
