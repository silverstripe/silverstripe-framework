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
	protected $maxLength;

	/**
	 * Returns an input field.
	 *
	 * @param string $name
	 * @param null|string $title
	 * @param string $value
	 * @param null|int $maxLength
	 * @param null|Form $form
	 */
	public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null) {
		if($maxLength) {
			$this->setMaxLength($maxLength);
		}

		if($form) {
			$this->setForm($form);
		}

		parent::__construct($name, $title, $value);
	}

	/**
	 * @param int $maxLength
	 *
	 * @return static
	 */
	public function setMaxLength($maxLength) {
		$this->maxLength = $maxLength;

		return $this;
	}

	/**
	 * @return null|int
	 */
	public function getMaxLength() {
		return $this->maxLength;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		$maxLength = $this->getMaxLength();

		$attributes = array();

		if($maxLength) {
			$attributes['maxLength'] = $maxLength;
			$attributes['size'] = min($maxLength, 30);
		}

		return array_merge(
			parent::getAttributes(),
			$attributes
		);
	}

	/**
	 * @return string
	 */
	public function InternallyLabelledField() {
		if(!$this->value) {
			$this->value = $this->Title();
		}

		return $this->Field();
	}
}
