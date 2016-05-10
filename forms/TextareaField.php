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
	 * Value should be XML
	 *
	 * @var array
	 */
	private static $casting = array(
		'Value' => 'Text',
		'ValueEntities' => 'HTMLText',
	);

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
	 * Gets number of rows
	 *
	 * @return int
	 */
	public function getRows() {
		return $this->rows;
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
	 * Gets the number of columns in this textarea
	 *
	 * @return int
	 */
	public function getColumns() {
		return $this->cols;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'rows' => $this->getRows(),
				'cols' => $this->getColumns(),
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
	 * Return value with all values encoded in html entities
	 *
	 * Invoke with $ValueEntities.RAW to suppress HTMLText parsing shortcodes.
	 *
	 * @return string Raw HTML
	 */
	public function ValueEntities() {
		return htmlentities($this->Value(), ENT_COMPAT, 'UTF-8');
	}
}
