<?php
/**
 * GridFieldFilter alters the gridfield with some filtering fields in the header of each column
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldFilter extends GridFieldRowSet {
	
	/**
	 * Location in the gridfield to renders this RowSet
	 *
	 * @var string
	 */
	public static $location = 'head';

	/**
	 *
	 * @var string
	 */
	public static $extra_columns = 2;
	
	/**
	 *
	 * @var array
	 */
	protected $filterFields = array();

	/**
	 * 
	 * @param GridField $gridField 
	 */
	public function __construct(GridField $gridField) {
		Requirements::javascript('sapphire/javascript/GridField.js');

		parent::__construct($gridField, 'GridFieldFilter');
		
		// Needs to generate children on construct, otherwise the filtering doesnt work
		$cols = $gridField->DisplayFields;
		foreach ($cols as $col) {
			$field = new TextField('SetFilter'.$col, $col);
			$field->addExtraClass('ss-gridfield-filter');
			$this->filterFields[] = $field;
			$this->push($field);
		}
	}

	/**
	 * Set up the children field prior to rendering
	 * 
	 * NOP
	 * 
	 * @return void
	 */
	public function generateChildren() {}

	/**
	 * Get the html content for all child fields
	 *
	 * @return string - HTML
	 */
	public function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		$filterFields = '<tr><th class="extra">'.implode("</th>\n<th class='extra'>", $content).'</th>';
		
		// @todo Find a better way to get the action buttons at the end of the row, not on a new row, and define the alter action instances in generateChildren
		$grid = $this->gridField;
		
		$cols = $grid->DisplayFields;
		$dependantFields = array();
		foreach ($cols as $col) {
			$dependantFields[] = 'SetFilter'.$col;
		}
		
		$setFilter = new GridField_AlterAction($grid, 'SetFilter', 'Filter');
		$setFilter->addExtraClass('ss-gridfield-button');
		$setFilter->stateChangeOnTrigger(array('Filter.SetFilter'=>1));
		$setFilter->applyStateFromFieldsOnTrigger('Filter.Criteria', $this->filterFields);
		
		$resetFilter = new GridField_AlterAction($grid, 'ResetFilter', 'Reset');
		$resetFilter->stateChangeOnTrigger(array('Filter.ResetFilter'=>1));
		$resetFilter->addExtraClass('ss-gridfield-button');
		
		$filterFields.= '<th class="extra">'.$setFilter->forTemplate().'</th>';
		$filterFields.= '<th class="extra">'.$resetFilter->forTemplate().'</th>';
		$filterFields.='</tr>';
		return $filterFields;
	}
}
