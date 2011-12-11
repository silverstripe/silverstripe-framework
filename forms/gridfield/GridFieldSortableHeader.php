<?php

class GridFieldSortableHeader extends GridFieldElement {
	static $location = 'head';

	function __construct($gridField) {
		parent::__construct($gridField, 'GridFieldSortableHeader');
	}

	function generateChildren() {
		$grid = $this->GridField;
		$state = $grid->State;
		$cols = $grid->DisplayFields;

		foreach ($cols as $col) {
			$field = new GridField_AlterAction($grid, 'SetOrder'.$col, $col);
			$field->stateChangeOnTrigger(array(
				'Sorting.Order' => array($col => $state->Sorting->getToggledOrder($col))
			));

			$this->push($field);
		}
	}

	function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		return '<tr><th>'.implode("</th>\n<th>", $content).'</th></tr>';
	}

}