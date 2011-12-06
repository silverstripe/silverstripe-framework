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
class GridFieldPaginator implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {

	protected $currentPage = 1;

	protected $itemsPerPage = 25;

	/**
	 * Which template to use for rendering
	 * 
	 * @var string $itemClass
	 */
	protected $itemClass = 'GridFieldPaginator_Row';

	public function __construct($itemsPerPage=25) {
		$this->itemsPerPage = $itemsPerPage;
	}

	public function getActions($gridField) {
		return array('paginate');
	}

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName !== 'paginate') {
			return;
		}
		$state = $gridField->State->GridFieldPaginator;
		$this->currentPage = $state->currentPage = (int)$arguments;
	}

	/** Duck check to see if list support methods we need to paginate */
	protected function getListPaginatable(SS_List $list) {
		// If no list yet, not paginatable
		if (!$list) return false;
		// Check for methods we use
		if(!method_exists($list, 'getRange')) return false;
		if(!method_exists($list, 'limit')) return false;
		// Default it true
		return true;
	}

	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		if(!$this->getListPaginatable($dataList)) {
			return $dataList;
		}
		if(!$this->currentPage) {
			return $dataList->getRange(0, (int)$this->itemsPerPage);
		}
		$startRow = $this->itemsPerPage*($this->currentPage-1);
		return $dataList->getRange((int)$startRow, (int)$this->itemsPerPage);
	}

	public function getHTMLFragments($gridField) {
		Requirements::javascript(SAPPHIRE_DIR.'/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_DIR.'/javascript/GridField.js');
		
		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList;
		
		$countList = clone $gridField->List;
		$totalRows = $countList->limit(null)->count();
		$totalPages = ceil($totalRows/$this->itemsPerPage);
		for($idx=1; $idx<=$totalPages; $idx++) {
			if($idx == $this->currentPage) {
				$field = new LiteralField('pagination_'.$idx, $idx);
			} else {
				$field = new GridField_Action($gridField, 'pagination_'.$idx, $idx, 'paginate', $idx);
				$field->addExtraClass('ss-gridfield-button');
			}
			
			$forTemplate->Fields->push($field);
		}
		if(!$forTemplate->Fields->Count()) {
			return array();
		}
		return array(
			'footer' => $forTemplate->renderWith('GridFieldPaginator_Row', array('Colspan'=>count($gridField->getColumns()))),
		);
	}
}
