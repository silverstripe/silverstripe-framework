<?php

abstract class GridFieldElement extends CompositeField {
	static $location = 'body';

	/** @var GridField - the gridField this element is a part of */
	protected $gridField = null;

	function __construct($gridField, $name) {
		$this->gridField = $gridField;

		CompositeField::__construct();
		FormField::__construct($name);
	}

	/** @return GridField */
	public function getGridField(){
		return $this->gridField;
	}

	function FieldHolder() {
		$this->generateChildren();
		return $this->getChildContent();
	}

	abstract function generateChildren() ;

	function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		return implode("\n", $content);
	}
}
