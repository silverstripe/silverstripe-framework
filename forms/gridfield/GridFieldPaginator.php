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
	protected $currentPage = 1;

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
		$this->currentPage = $state->currentPage = (int)$arguments;
	}

	/**
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
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

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getHTMLFragments($gridField) {
		// Figure out which page and record range we're on
		$countList = clone $gridField->List;
		$totalRows = $countList->limit(null)->count();
		$totalPages = ceil($totalRows/$this->itemsPerPage);
		$firstShownRecord = ($this->currentPage - 1) * $this->itemsPerPage + 1;
		$lastShownRecord = $this->currentPage * $this->itemsPerPage;
		if($lastShownRecord > $totalRows)
			$lastShownRecord = $totalRows;


		// Ten pages back button
		$prev10PageNum = $this->currentPage - 10 <= 1 ? 1 : $this->currentPage - 10;
		$prev10Page = new GridField_Action($gridField, 'pagination_prev10', '-10', 'paginate', 1);
		$prev10Page->addExtraClass('ss-gridfield-prev10page');
		if($this->currentPage == 1)
			$prev10Page = $prev10Page->performDisabledTransformation();

		// Previous page button
		$previousPageNum = $this->currentPage <= 1 ? 1 : $this->currentPage - 1;
		$previousPage = new GridField_Action($gridField, 'pagination_prev', 'Previous', 'paginate', $previousPageNum);
		$previousPage->addExtraClass('ss-gridfield-previouspage');
		if($this->currentPage == 1)
			$previousPage = $previousPage->performDisabledTransformation();

		// Next page button
		$nextPageNum = $this->currentPage >= $totalPages ? $totalPages : $this->currentPage + 1;
		$nextPage = new GridField_Action($gridField, 'pagination_next', 'Next', 'paginate', $nextPageNum);
		$nextPage->addExtraClass('ss-gridfield-nextpage');
		if($this->currentPage == $totalPages)
			$nextPage = $nextPage->performDisabledTransformation();

		// Ten pages forward button
		$next10PageNum = $this->currentPage + 10 >= $totalPages ? $totalPages : $this->currentPage + 10;
		$next10Page = new GridField_Action($gridField, 'pagination_next10', '+10', 'paginate', $next10PageNum);
		$next10Page->addExtraClass('ss-gridfield-next10page');
		if($this->currentPage == $totalPages)
			$next10Page = $next10Page->performDisabledTransformation();


		// Render in template
		$forTemplate = new ArrayData(array(
			'Previous10Page' => $prev10Page,
			'PreviousPage' => $previousPage,
			'CurrentPageNum' => $this->currentPage,
			'NumPages' => $totalPages,
			'NextPage' => $nextPage,
			'Next10Page' => $next10Page,
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
