<?php
/**
 * Base class for any element that are attached (child) to a GridField
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
abstract class GridFieldRowSet extends CompositeField {
	
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
	 * Returns the rendered HTML for this elements children
	 *
	 * @return string
	 */
	public function FieldHolder() {
		$this->generateChildren();
		return $this->getChildContent();
	}

	/**
	 * Get the html content for all child fields
	 *
	 * @return string - HTML
	 */
	protected function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		return implode("\n", $content);
	}
	
	/**
	 * Set up the children field prior to rendering
	 * 
	 * @return void
	 */
	abstract function generateChildren();
}
