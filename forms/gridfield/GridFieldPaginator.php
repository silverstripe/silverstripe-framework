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

	/** @var string */
	protected $template = 'GridFieldPaginator';
	
	/** @param GridField $gridField */
	public function __construct(GridField $gridField) {
		Requirements::javascript('sapphire/javascript/GridFieldPaginator.js');

		parent::__construct($gridField, 'GridFieldPaginator');
	}

	function generateChildren() {
		$pagination = $this->gridField->getState()->Pagination;
		$totalPages = $pagination->TotalPages;

		for($idx=1; $idx<=$totalPages; $idx++) {
			$field = new GridField_AlterAction($this->gridField, 'SetPage'.$idx, $idx);
			$field->stateChangeOnTrigger(array(
				'Pagination.Page' => $idx
			));

			$this->push($field);
		}
	}

	function getChildContent() {
		$content = array();
		foreach($this->FieldList() as $subfield) $content[] = $subfield->forTemplate();
		return '<tr><td>'.implode("\n", $content).'</td></tr>';
	}
}
