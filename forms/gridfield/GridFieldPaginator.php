<?php

/**
 * GridFieldPaginator decorates the GridFieldPresenter with the possibility to
 * paginate the GridField.
 * 
 * @see GridField
 * @see GridFieldPresenter
 * @package sapphire
 */
class GridFieldPaginator extends GridFieldElement {
	static $location = 'foot';

	/** @param GridField $gridField */
	public function __construct(GridField $gridField) {
		Requirements::javascript(SAPPHIRE_DIR.'/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_DIR.'/javascript/GridField.js');

		parent::__construct($gridField, 'GridFieldPaginator');
	}

	public function generateChildren() {
		$pagination = $this->gridField->getState()->Pagination;
		$totalPages = $pagination->TotalPages;

		for($idx=1; $idx<=$totalPages; $idx++) {
			$field = new GridField_AlterAction($this->gridField, 'SetPage'.$idx, $idx);
			$field->stateChangeOnTrigger(array(
				'Pagination.Page' => $idx
			));
			$field->addExtraClass('ss-gridfield-button');
			$this->push($field);
		}
	}

	public function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		return '<tr><td colspan="'.$this->gridField->getColumnCount().'">'.implode("\n", $content).'</td></tr>';
	}
}
