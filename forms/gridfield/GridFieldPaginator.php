<?php

/**
 * GridFieldPaginator decorates the GridFieldPresenter with the possibility to
 * paginate the GridField.
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldPaginator extends GridFieldRowSet {
	
	/**
	 * Location in the gridfield to renders this RowSet
	 *
	 * @var string
	 */
	static $location = 'foot';
	
	/**
	 *
	 * @var string
	 */
	public static $extra_columns = 0;

	/**
	 * 
	 * @param GridField $gridField 
	 */
	public function __construct(GridField $gridField) {
		Requirements::javascript(SAPPHIRE_DIR.'/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_DIR.'/javascript/GridField.js');
		parent::__construct($gridField, 'GridFieldPaginator');
	}
	
	/**
	 * Set up the children field prior to rendering
	 * 
	 * @return void
	 */
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
	
	/**
	 * Get the html content for all child fields
	 *
	 * @return string - HTML
	 */
	public function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		return '<tr><td colspan="'.$this->gridField->getColumnCount().'">'.implode("\n", $content).'</td></tr>';
	}
}
