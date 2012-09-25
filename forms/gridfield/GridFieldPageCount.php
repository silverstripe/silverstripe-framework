<?php
/**
 * GridFieldPage displays a simple current page count summary.
 * E.g. "View 1 - 15 of 32"
 * 
 * @package framework
 * @subpackage fields-relational
 */
class GridFieldPageCount implements GridField_HTMLProvider, GridField_DataManipulator {

	/**
	 * @var string placement indicator for this control
	 */
	protected $targetFragment;
	
	/**
	 *
	 * @var int
	 */
	protected $itemsPerPage = 15;

	/**
	 * Which template to use for rendering
	 * 
	 * @var string
	 */
	protected $itemClass = 'GridFieldPageCount';

	/**
	 * @param Int
	 */
	public function setItemsPerPage($num) {
		$this->itemsPerPage = $num;
		return $this;
	}

	/**
	 * @return Int
	 */
	public function getItemsPerPage() {
		return $this->itemsPerPage;
	}

	/**
	 *
	 * @param int $itemsPerPage - How many items should be displayed per page
	 */
	public function __construct($itemsPerPage=null, $targetFragment = 'before') {
		if($itemsPerPage) $this->itemsPerPage = $itemsPerPage;
		
		$this->targetFragment = $targetFragment;
	}

	protected $totalItems = 0;

	/**
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		
		$this->totalItems = $dataList->count();
		
		$state = $gridField->State->GridFieldPaginator;
		if(!is_int($state->currentPage)) {
			$state->currentPage = 1;
		}

		// Don't actually limit this list; Leave this up to the GridFieldPaginator
		return $dataList;
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getHTMLFragments($gridField) {
		
		$state = $gridField->State->GridFieldPaginator;
		if(!is_int($state->currentPage)) {
			$state->currentPage = 1;
		}

		// Figure out which page and record range we're on
		$totalRows = $this->totalItems;
		if(!$totalRows) return array();

		$totalPages = (int)ceil($totalRows/$this->itemsPerPage);
		if($totalPages == 0) {
			$totalPages = 1;
		}
		
		// First record
		$firstShownRecord = ($state->currentPage - 1) * $this->itemsPerPage + 1;
		if($firstShownRecord > $totalRows) {
			$firstShownRecord = $totalRows;
		}
		
		// Last record
		$lastShownRecord = $state->currentPage * $this->itemsPerPage;
		if($lastShownRecord > $totalRows) {
			$lastShownRecord = $totalRows;
		}
		
		$forTemplate = new ArrayData(array(
			'OnlyOnePage' => ($totalPages === 1),
			'FirstShownRecord' => $firstShownRecord,
			'LastShownRecord' => $lastShownRecord,
			'NumRecords' => $totalRows
		));
		return array(
			$this->targetFragment => $forTemplate->renderWith($this->itemClass)
		);
	}

}
