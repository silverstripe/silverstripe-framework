<?php
/**
 * GridFieldPaginator paginates the gridfields list and adds controlls to the
 * bottom of the gridfield.
 * 
 * @package framework
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
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	protected $throwExceptionOnBadDataType = true;

	/**
	 *
	 * @param int $itemsPerPage - How many items should be displayed per page
	 */
	public function __construct($itemsPerPage=null) {
		if($itemsPerPage) $this->itemsPerPage = $itemsPerPage;
	}
	
	/**
	 * Determine what happens when this component is used with a list that isn't {@link SS_Filterable}.
	 * 
	 *  - true: An exception is thrown
	 *  - false: This component will be ignored - it won't make any changes to the GridField.
	 * 
	 * By default, this is set to true so that it's clearer what's happening, but the predefined
	 * {@link GridFieldConfig} subclasses set this to false for flexibility.
	 */
	public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType) {
		$this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType; 
	}

	/**
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	public function getThrowExceptionOnBadDataType() {
		return $this->throwExceptionOnBadDataType;
	}
	
	/**
	 * Check that this dataList is of the right data type.
	 * Returns false if it's a bad data type, and if appropriate, throws an exception.
	 */
	protected function checkDataType($dataList) {
		if($dataList instanceof SS_Limitable) {
			return true;
		} else {
			if($this->throwExceptionOnBadDataType) {
				throw new LogicException(get_class($this) . " expects an SS_Limitable list to be passed to the GridField.");
			}
			return false;
		}
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;
		
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
		if(!$this->checkDataType($gridField->getList())) return;
		
		if($actionName !== 'paginate') {
			return;
		}
		$state = $gridField->State->GridFieldPaginator;
		$state->currentPage = (int)$arguments;
	}
	
	protected $totalItems = 0;

	/**
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		if(!$this->checkDataType($dataList)) return $dataList;
		
		$this->totalItems = $gridField->getList()->count();
		
		$state = $gridField->State->GridFieldPaginator;
		if(!is_int($state->currentPage)) {
			$state->currentPage = 1;
		}

		if(!($dataList instanceof SS_Limitable)) {
			return $dataList;
		}
		if(!$state->currentPage) {
			return $dataList->limit((int)$this->itemsPerPage);
		}
		$startRow = $this->itemsPerPage * ($state->currentPage - 1);
		return $dataList->limit((int)$this->itemsPerPage, (int)$startRow);
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getHTMLFragments($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;
		
		$state = $gridField->State->GridFieldPaginator;
		if(!is_int($state->currentPage))
			$state->currentPage = 1;

		// Figure out which page and record range we're on
		$totalRows = $this->totalItems;
		if(!$totalRows) return array();

		$totalPages = (int)ceil($totalRows/$this->itemsPerPage);
		if($totalPages == 0)
			$totalPages = 1;
		$firstShownRecord = ($state->currentPage - 1) * $this->itemsPerPage + 1;
		if($firstShownRecord > $totalRows)
			$firstShownRecord = $totalRows;
		$lastShownRecord = $state->currentPage * $this->itemsPerPage;
		if($lastShownRecord > $totalRows)
			$lastShownRecord = $totalRows;
		
		// If there is only 1 page for all the records in list, we don't need to go further 
		// to sort out those first page, last page, pre and next pages, etc
		// we are not render those in to the paginator.
		if($totalPages === 1){
			$forTemplate = new ArrayData(array(
				'OnlyOnePage' => true,
				'FirstShownRecord' => $firstShownRecord,
				'LastShownRecord' => $lastShownRecord,
				'NumRecords' => $totalRows
			));
		}else{
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
				'OnlyOnePage' => false,
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
		}
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

}
