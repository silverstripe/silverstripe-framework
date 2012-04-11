<?php
/**
 * Transformation that will make a form printable.
 * Builds on readonly forms with different behaviour for tabsets.
 * @package forms
 * @subpackage transformations
 */
class PrintableTransformation extends ReadonlyTransformation {
	function transformTabSet($field) {
		$transformedField = new PrintableTransformation_TabSet($field->Tabs()->transform($this));
		$transformedField->Title = $field->Title();
		$transformedField->TabSet = $field->TabSet;
		return $transformedField;
	}
}

/**
 * Class representing printable tabsets
 * @package forms
 * @subpackage transformations
 */
class PrintableTransformation_TabSet extends TabSet {
	function __construct($tabs) {
		$this->children = $tabs;
		CompositeField::__construct($tabs);
	}
	
	function FieldHolder($properties = array()) {
		// This gives us support for sub-tabs.
		$tag = ($this->tabSet) ? "h2>" : "h1>";
		
		foreach($this->children as $tab) {
			$retVal .= "<$tag" . $tab->Title() . "</$tag\n";
			$retVal .= $tab->FieldHolder();
		}
		return $retVal;
		
	}
	
	
}

