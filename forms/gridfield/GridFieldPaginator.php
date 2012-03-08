<?php
/**
 * GridFieldPaginator paginates the gridfields list and adds controlls to the
 * bottom of the gridfield.
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldPaginator implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {
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
	protected $itemClass = 'GridFieldPaginator_Row';

	/**
	 *
	 * @param int $itemsPerPage - How many items should be displayed per page
	 */
	public function __construct($itemsPerPage=null) {
		if($itemsPerPage) $this->itemsPerPage = $itemsPerPage;
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		return array('paginate');
	}

	/**
	 *
	 * @param GridField $gridField
	 * @param string $actionName
	 * @param string $arguments
	 * @param array $data
	 * @return void
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName !== 'paginate') {
			return;
		}
		$state = $gridField->State->GridFieldPaginator;
		$state->currentPage = (int)$arguments;
	}

	/**
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		$state = $gridField->State->GridFieldPaginator;
		if(!is_int($state->currentPage))
			$state->currentPage = 1;

		if(!$this->getListPaginatable($dataList)) {
			return $dataList;
		}
		if(!$state->currentPage) {
			return $dataList->getRange(0, (int)$this->itemsPerPage);
		}
		$startRow = $this->itemsPerPage * ($state->currentPage - 1);
		return $dataList->getRange((int)$startRow, (int)$this->itemsPerPage);
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getHTMLFragments($gridField) {
		$state = $gridField->State->GridFieldPaginator;
		if(!is_int($state->currentPage))
			$state->currentPage = 1;

		// Figure out which page and record range we're on
		$countList = clone $gridField->List;
		$totalRows = $countList->limit(null)->count();
		if(!$totalRows) return array();

		$totalPages = ceil($totalRows/$this->itemsPerPage);
		if($totalPages == 0)
			$totalPages = 1;
		$firstShownRecord = ($state->currentPage - 1) * $this->itemsPerPage + 1;
		if($firstShownRecord > $totalRows)
			$firstShownRecord = $totalRows;
		$lastShownRecord = $state->currentPage * $this->itemsPerPage;
		if($lastShownRecord > $totalRows)
			$lastShownRecord = $totalRows;


		// First page button
		$firstPage = new GridField_FormAction($gridField, 'pagination_first', 'First', 'paginate', 1);
		$firstPage->addExtraClass('ss-gridfield-firstpage');
		if($state->currentPage == 1)
			$firstPage = $firstPage->performDisabledTransformation();

		// Previous page button
		$previousPageNum = $state->currentPage <= 1 ? 1 : $state->currentPage - 1;
		$previousPage = new GridField_FormAction($gridField, 'pagination_prev', 'Previous', 'paginate', $previousPageNum);
		$previousPage->addExtraClass('ss-gridfield-previouspage');
		if($state->currentPage == 1)
			$previousPage = $previousPage->performDisabledTransformation();

		// Next page button
		$nextPageNum = $state->currentPage >= $totalPages ? $totalPages : $state->currentPage + 1;
		$nextPage = new GridField_FormAction($gridField, 'pagination_next', 'Next', 'paginate', $nextPageNum);
		$nextPage->addExtraClass('ss-gridfield-nextpage');
		if($state->currentPage == $totalPages)
			$nextPage = $nextPage->performDisabledTransformation();

		// Last page button
		$lastPage = new GridField_FormAction($gridField, 'pagination_last', 'Last', 'paginate', $totalPages);
		$lastPage->addExtraClass('ss-gridfield-lastpage');
		if($state->currentPage == $totalPages)
			$lastPage = $lastPage->performDisabledTransformation();


		// Render in template
		$forTemplate = new ArrayData(array(
			'FirstPage' => $firstPage,
			'PreviousPage' => $previousPage,
			'CurrentPageNum' => $state->currentPage,
			'NumPages' => $totalPages,
			'NextPage' => $nextPage,
			'LastPage' => $lastPage,
			'FirstShownRecord' => $firstShownRecord,
			'LastShownRecord' => $lastShownRecord,
			'NumRecords' => $totalRows
		));
		return array(
			'footer' => $forTemplate->renderWith('GridFieldPaginator_Row', array('Colspan'=>count($gridField->getColumns()))),
		);
	}

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
}
