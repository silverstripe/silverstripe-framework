<?php

/**
 * @package forms
 * @subpackage fields-basic
 */

/**
 * Multi-line text area.
 * @package forms
 * @subpackage fields-basic
 */
class TextareaField extends FormField {
	protected $rows, $cols, $disabled = false, $readonly = false;
	
	/**
	 * Create a new multi-line text area field.
	 * @param $name Field name
	 * @param $title Field title
	 * @param $rows The number of rows
	 * @param $cols The number of columns
	 * @param $value The current value
	 * @param $form The parent form.  Auto-set when the field is placed in a form.
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
		$classAttr = '';
		if( $this->readonly ) {
			$classAttr .= 'class="readonly';
			if( $extraClass = trim( $this->extraClass() ) )
				$classAttr .= " $extraClass";
			$classAttr .= '"';
		}
		else if( $extraClass = trim( $this->extraClass() ) )
			$classAttr .= 'class="' . $extraClass . '"';
		
		$disabled = $this->disabled ? " disabled=\"disabled\"" : "";
		$readonly = $this->readonly ? " readonly=\"readonly\"" : "";
		
		if( $this->readonly )
			return "<span $disabled$readonly $classAttr id=\"" . $this->id() . "\" name=\"{$this->name}\" rows=\"{$this->rows}\" cols=\"{$this->cols}\">" . ( $this->value ? Convert::raw2att( $this->value ) : '<i>(not set)</i>' ) . "</span>";
		else
			return "<textarea $disabled$readonly $classAttr id=\"" . $this->id() . "\" name=\"{$this->name}\" rows=\"{$this->rows}\" cols=\"{$this->cols}\">".Convert::raw2att($this->value)."</textarea>";
	}
	
	/**
	 * Performs a readonly transformation on this field. You should still be able
	 * to copy from this field, and it should still send when you submit
	 * the form it's attached to.
	 * The element shouldn't be both disabled and readonly at the same time.
	 */
	function performReadonlyTransformation() {
		$this->readonly = true;
		$this->disabled = false;
		return $this;
	}
	
	/**
	 * Performs a disabled transformation on this field. You shouldn't be able to
	 * copy from this field, and it should not send any data when you submit the 
	 * form it's attached to.
	 * The element shouldn't be both disabled and readonly at the same time.
	 */
	function performDisabledTransformation() {
		$this->disabled = true;
		$this->readonly = false;
		return $this;
	}
	
	function Type() {
		return parent::Type() . ( $this->readonly ? ' readonly' : '' ); 
	}
}
?>
