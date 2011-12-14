<?php

abstract class GridFieldElement extends CompositeField {
	
	/**
	 *
	 * @var string - where in the gridfield this element should be rendered, positions available are:
	 * head, body, foot or misc
	 */
	public static $location = 'body';

	/** @var GridField - the gridField this element is a part of */
	protected $gridField = null;

	/**
	 *
	 * @param GridField $gridField
	 * @param string $name 
	 */
	public function __construct(GridField $gridField, $name) {
		$this->gridField = $gridField;
		CompositeField::__construct();
		FormField::__construct($name);
	}

	/** @return GridField */
	public function getGridField(){
		return $this->gridField;
	}
	
	/**
	 *
	 * @return string - the rendered HTML for this element
	 */
	public function FieldHolder() {
		$this->generateChildren();
		return $this->getChildContent();
	}

	/**
	 *
	 * @return type 
	 */
	protected function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		return implode("\n", $content);
	}
	
	abstract function generateChildren();
}
