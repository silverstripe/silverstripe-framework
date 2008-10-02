<?php
/**
 * It's a LiteralField ... with a Label
 * 
 * @package forms
 * @subpackage fields-dataless
 * 
 * @deprecated If you need to have a label for your literal field, just put the
 * HTML into a LiteralField, or use a custom form template to separate your
 * presentation/content from the data fields.
 * 
 * @see http://doc.silverstripe.com/doku.php?id=form#using_a_custom_template
 */
class LabelledLiteralField extends LiteralField {
	
	function __construct( $name, $title, $content ) {
		parent::__construct($name, $content);
		user_error('LabelledLiteralField is deprecated. Please see the @deprecated note in LabelledLiteralField.php', E_USER_NOTICE);
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