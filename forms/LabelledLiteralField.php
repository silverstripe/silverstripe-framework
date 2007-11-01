<?php
/**
 * It's a LiteralField ... with a Label
 */
class LabelledLiteralField extends LiteralField {
	
	function __construct( $name, $title, $content ) {
		parent::__construct( $name, $content );
		$this->setTitle( $title );
	}
	
	function FieldHolder() {
		return FormField::FieldHolder();
	}
	
	function Field() {
		return is_object($this->content) ? $this->content->forTemplate() : $this->content; 
	}
}
?>