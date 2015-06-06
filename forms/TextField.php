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

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'maxlength' => $this->getMaxLength(),
				'size' => ($this->getMaxLength()) ? min($this->getMaxLength(), 30) : null
			)
		);
	}

	public function InternallyLabelledField() {
		if(!$this->value) $this->value = $this->Title();
		return $this->Field();
	}

	public function validate(Validator $validator) {

		$form = $this->getForm();
		if ($form) {
			$record = $form->getRecord();
			if ($record) {
				foreach($record->db() as $fieldName => $fieldType) {
					if ($fieldName == $this->name) {
						$fieldObject = $record->dbObject($fieldName);
						if ($fieldObject instanceof Varchar) {
							if (strlen($this->value) > $fieldObject->getSize()) {
								$validator->validationError(
									$this->name,
									_t(
										'TextField.VALUE_TOO_LONG',
										"This field should only contain a maximum of {size} characters.",
										array(
											'size' => $fieldObject->getSize()
										)
									),
									"validation"
								);
								return false;
							}
						}
					}
				}
			}
		}

		return false;

	}

}
