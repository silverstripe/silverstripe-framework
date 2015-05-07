<?php

/**
 * Hidden field.
 *
 * @package forms
 * @subpackage fields-dataless
 */
class HiddenField extends FormField {
	/**
	 * @param array $properties
	 *
	 * @return string
	 */
	public function FieldHolder($properties = array()) {
		return $this->Field($properties);
	}

	/**
	 * @return static
	 */
	public function performReadonlyTransformation() {
		$clone = clone $this;

		$clone->setReadonly(true);

		return $clone;
	}

	/**
	 * @return bool
	 */
	public function IsHidden() {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'type' => 'hidden',
			)
		);
	}
}
