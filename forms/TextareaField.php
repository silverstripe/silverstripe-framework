<?php

/**
 * TextareaField creates a multi-line text field,
 * allowing more data to be entered than a standard
 * text field. It creates the <textarea> tag in the
 * form HTML.
 *
 * <code>
 * new TextareaField(
 *    $name = "description",
 *    $title = "Description",
 *    $value = "This is the default description"
 * );
 * </code>
 *
 * @package forms
 * @subpackage fields-basic
 */
class TextareaField extends FormField {
	/**
	 * Visible number of text lines.
	 *
	 * @var int
	 */
	protected $rows = 5;

	/**
	 * Visible number of text columns.
	 *
	 * @var int
	 */
	protected $cols = 20;

	/**
	 * Set the number of rows in the textarea
	 *
	 * @param int $rows
	 *
	 * @return $this
	 */
	public function setRows($rows) {
		$this->rows = $rows;

		return $this;
	}

	/**
	 * Set the number of columns in the textarea
	 *
	 * @param int $cols
	 *
	 * @return $this
	 */
	public function setColumns($cols) {
		$this->cols = $cols;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'rows' => $this->rows,
				'cols' => $this->cols,
				'value' => null,
				'type' => null
			)
		);
	}


	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		$parent = parent::Type();

		if($this->readonly) {
			return $parent . ' readonly';
		}

		return $parent;
	}

	/**
	 * @return string
	 */
	public function Value() {
		return htmlentities($this->value, ENT_COMPAT, 'UTF-8');
	}
}
