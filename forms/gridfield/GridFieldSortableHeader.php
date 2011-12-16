<?php

class GridFieldSortableHeader extends GridFieldElement {
	static $location = 'head';

	function __construct($gridField) {
		Requirements::javascript(SAPPHIRE_DIR.'/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_DIR.'/javascript/GridField.js');
		parent::__construct($gridField, 'GridFieldSortableHeader');
	}

	function generateChildren() {
		$grid = $this->GridField;
		$state = $grid->State;
		$cols = $grid->DisplayFields;
		
		$order = $state->Sorting->getOrder();
		
		foreach ($cols as $col) {
			$field = new GridField_AlterAction($grid, 'SetOrder'.$col, $col);
			$field->stateChangeOnTrigger(array(
				'Sorting.Order' => array($col => $state->Sorting->getToggledOrder($col))
			));
			$field->addExtraClass('ss-gridfield-button');
			if(property_exists($order,$col)){
				$field->addExtraClass('ss-gridfield-sorted');
			}
			$this->push($field);
		}
	}

	function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		$html = '<tr><th class="main">'.implode("</th>\n<th class=\"main\">", $content).'</th>';
		if($this->gridField->getExtraColumnsCount()) {
			if($this->gridField->getExtraColumnsCount()) {
				$html .= '<th colspan="'.$this->gridField->getExtraColumnsCount().'"></th>';
			}
		}
		$html.= '</tr>';
		return $html;
	}
}