<?php

/**
 * GridFieldFilter alters the gridfield with some filtering fields in the header of each column
 * 
 * @see GridField
 * @package sapphire
 */
class GridFieldFilter extends GridFieldElement {
	static $location = 'head';

	protected $filterFields = array();

	/** @param GridField $gridField */
	public function __construct(GridField $gridField) {
		Requirements::javascript('sapphire/javascript/GridField.js');

		parent::__construct($gridField, 'GridFieldFilter');
		
		$cols = $gridField->DisplayFields;
		foreach ($cols as $col) {
			$this->filterFields[] = $field = new TextField('SetFilter'.$col, $col);
			$this->push($field);
		}
	}

	function generateChildren() {
		$grid = $this->gridField;
		$filter = $grid->getState()->Filter;
		$cols = $grid->DisplayFields;
		
		//$this->push(new GridField_AlterAction($grid, 'SetFilter', 'Filter'));
		//$this->push(new GridField_AlterAction($grid, 'ResetFilter', 'Reset'));
	}

	function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		$filterFields = '<tr><th>'.implode("</th>\n<th>", $content).'</th></tr>';
		
		// TODO Find a better way to get the action buttons at the end of the row, not on a new row, and define the alter action instances in generateChildren
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
		$filterFields.= '<tr><th colspan="'.max(1, count($grid->DisplayFields) - 2).'"></th>';
		$filterFields.= '<th>'.$setFilter->forTemplate().'</th>';
		$filterFields.= '<th>'.$resetFilter->forTemplate().'</th>';
		$filterFields.='</tr>';
		return $filterFields;
	}
}
