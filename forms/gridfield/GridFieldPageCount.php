<?php
/**
 * GridFieldPage displays a simple current page count summary.
 * E.g. "View 1 - 15 of 32"
 * 
 * Depends on GridFieldPaginator being added to the same gridfield
 * 
 * @package framework
 * @subpackage fields-relational
 */
class GridFieldPageCount implements GridField_HTMLProvider {

	/**
	 * @var string placement indicator for this control
	 */
	protected $targetFragment;

	/**
	 * Which template to use for rendering
	 * 
	 * @var string
	 */
	protected $itemClass = 'GridFieldPageCount';

	/**
	 * @param string $targetFrament The fragment indicating the placement of this page count
	 */
	public function __construct($targetFragment = 'before') {
		$this->targetFragment = $targetFragment;
	}
	
	/**
	 * Retrieves/Sets up the state object used to store and retrieve information
	 * about the current paging details of this GridField
	 * @param GridField $gridField
	 * @return GridState_Data 
	 */
	protected function getGridPagerState(GridField $gridField) {
		$state = $gridField->State->GridFieldPaginator;
		
		// Force the state to the initial page if none is set
		if(empty($state->currentPage)) $state->currentPage = 1;
		
		// Set total items to default, if not passed from GridFieldPaginator
		if(empty($state->totalItems)) $state->totalItems = 0;
		
		// Set items per page to default, if not passed from GridFieldPaginator
		if(empty($state->itemsPerPage)) $state->itemsPerPage = 15;
		
		return $state;
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getHTMLFragments($gridField) {
		
		$state = $this->getGridPagerState($gridField);

		// Figure out which page and record range we're on
		$totalRows = $state->totalItems;
		if(!$totalRows) return array();

		$totalPages = (int)ceil($totalRows/$state->itemsPerPage);
		if($totalPages == 0) {
			$totalPages = 1;
		}
		
		// First record
		$firstShownRecord = ($state->currentPage - 1) * $state->itemsPerPage + 1;
		if($firstShownRecord > $totalRows) {
			$firstShownRecord = $totalRows;
		}
		
		// Last record
		$lastShownRecord = $state->currentPage * $state->itemsPerPage;
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
