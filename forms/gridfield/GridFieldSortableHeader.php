<?php
/**
 * GridFieldSortableHeader adds column headers to a gridfield that can also sort the columns
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldSortableHeader extends GridFieldRowSet {
	
	/**
	 * Location in the gridfield to renders this RowSet
	 *
	 * @var string
	 */
	static $location = 'head';

	/**
	 *
	 * @param GridField $gridField 
	 */
	public function __construct(GridField $gridField) {
		Requirements::javascript(SAPPHIRE_DIR.'/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_DIR.'/javascript/GridField.js');
		parent::__construct($gridField, 'GridFieldSortableHeader');
	}

	/**
	 * Set up the children field prior to rendering
	 * 
	 * @return void
	 */
	public function generateChildren() {
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
			if($order && property_exists($order, $col)){
				$field->addExtraClass('ss-gridfield-sorted');
			}
			$this->push($field);
		}
	}

	/**
	 * Get the html content for all child fields
	 *
	 * @return string - HTML
	 */
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