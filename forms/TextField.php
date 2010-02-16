<?php
/**
 * Text input field.
 * @package forms
 * @subpackage fields-basic
 */
class TextField extends FormField {

	/**
	 * @var Int
	 */
	protected $maxLength;
	
	/**
	 * Returns an input field, class="text" and type="text" with an optional maxlength
	 */
	function __construct($name, $title = null, $value = "", $maxLength = null, $form = null){
		$this->maxLength = $maxLength;
		
		parent::__construct($name, $title, $value, $form);
	}
	
	/**
	 * @param Int $length
	 */
	function setMaxLength($length) {
		$this->maxLength = $length;
	}
	
	/**
	 * @return Int
	 */
	function getMaxLength() {
		return $this->maxLength;
	}
	
	function Field() {
		$attributes = array(
			'type' => 'text',
			'class' => 'text' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => $this->Value(),
			'tabindex' => $this->getTabIndex(),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null,
			'size' => ($this->maxLength) ? min( $this->maxLength, 30 ) : null 
		);
		
		if($this->disabled) $attributes['disabled'] = 'disabled';
		
		return $this->createTag('input', $attributes);
	}
	
	function InternallyLabelledField() {
		if(!$this->value) $this->value = $this->Title();
		return $this->Field();
	}
	
}
?>