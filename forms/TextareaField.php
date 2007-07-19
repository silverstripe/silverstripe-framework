<?php
/**
 * Multi-line text area.
 */
class TextareaField extends FormField {
	protected $rows, $cols;
	
	/**
	 * Create a new multi-line text area field.
	 * @param name Field name
	 * @param title Field title
	 * @param rows The number of rows
	 * @param cols The number of columns
	 * @param value The current value
	 * @param form The parent form.  Auto-set when the field is placed in a form.
	 */
	function __construct($name, $title = "", $rows = 5, $cols = 20, $value = "", $form = null) {
		$this->rows = $rows;
		$this->cols = $cols;
		parent::__construct($name, $title, $value, $form);
	}
	
	/**
	 * Returns a <textarea> tag - used in templates.
	 */
	function Field() {
		if($extraClass = trim($this->extraClass())) {
			$classAttr = "class=\"$extraClass\"";
		} else {
			$classAttr = '';
		}
		
		return "<textarea $classAttr id=\"" . $this->id() . "\" name=\"{$this->name}\" rows=\"{$this->rows}\" cols=\"{$this->cols}\">".Convert::raw2att($this->value)."</textarea>";
	}	
}
?>
