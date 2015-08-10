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
	 * @return HTMLText
	 */
	public function FieldHolder($properties = []) {
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
			[
				'type' => 'hidden',
			]
		);
	}

	function SmallFieldHolder($properties = []) {
		return $this->FieldHolder($properties);
	}

}
