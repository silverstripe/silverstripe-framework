<?php
/**
 * TextareaField creates a multi-line text field,
 * allowing more data to be entered than a standard
 * text field. It creates the <textarea> tag in the
 * form HTML.
 * 
 * <b>Usage</b>
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
	 * @var int Visible number of text lines.
	 */
	protected $rows = 5;

	/**
	 * @var int Width of the text area (in average character widths)
	 */
	protected $cols = 20;

	/**
	 * Create a new textarea field.
	 * 
	 * @param $name Field name
	 * @param $title Field title
	 * @param $value The current value
	 */
	public function __construct($name, $title = null, $value = '') {
		if(count(func_get_args()) > 3) {
			Deprecation::notice('3.0', 'Use setRows() and setColumns() instead of constructor arguments');
		}

		parent::__construct($name, $title, $value);
	}

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
	 * Performs a disabled transformation on this field. You shouldn't be able to
	 * copy from this field, and it should not send any data when you submit the
	 * form it's attached to.
	 *
	 * The element shouldn't be both disabled and readonly at the same time.
	 */
	public function performDisabledTransformation() {
		$clone = clone $this;
		$clone->setDisabled(true);
		$clone->setReadonly(false);
		return $clone;
	}

	public function Type() {
		return parent::Type() . ($this->readonly ? ' readonly' : '');
	}
	
	/**
	 * Set the number of rows in the textarea
	 *
	 * @param int
	 */
	public function setRows($rows) {
		$this->rows = $rows;
		return $this;
	}
	
	/**
	 * Set the number of columns in the textarea
	 *
	 * @return int
	 */
	public function setColumns($cols) {
		$this->cols = $cols;
		return $this;
	}

	public function Value() {
		return htmlentities($this->value, ENT_COMPAT, 'UTF-8');
	}
}
