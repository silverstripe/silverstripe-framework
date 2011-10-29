<?php
/**
 * GridFieldPaginator decorates the GridFieldPresenter with the possibility to
 * paginate the GridField.
 * 
 * @see GridField
 * @see GridFieldPresenter
 * @package sapphire
 */
class GridFieldPaginator extends ViewableData {
	
	/**
	 *
	 * @var string
	 */
	protected $template = 'GridFieldPaginator';
	
	/**
	 *
	 * @var int
	 */
	protected $totalNumberOfPages = 0;
	
	/**
	 *
	 * @var int
	 */
	protected $currentPage = 0;
	
	/**
	 *
	 * @param int $totalNumberOfPages
	 * @param int $currentPage 
	 */
	public function __construct($totalNumberOfPages,$currentPage = 1) {
		Requirements::javascript('sapphire/javascript/GridFieldPaginator.js');
		$this->totalNumberOfPages = $totalNumberOfPages;
		$this->currentPage = $currentPage;
	}
	
	/**
	 * Returns the rendered template for GridField
	 *
	 * @return string 
	 */
	public function Render() {
		return $this->renderWith(array($this->template));
	}
	
	/**
	 * Returns a url to the last page in the result
	 *
	 * @return string 
	 */
	public function FirstLink() {
		if($this->haveNoPages()) {
			return false;
		}
		return 1;
	}
	
	/**
	 * Returns a url to the previous page in the result
	 *
	 * @return string 
	 */
	public function PreviousLink() {
		if($this->isFirstPage() || $this->haveNoPages()) {
			return false;
		}
		// Out of bounds
		if($this->currentPage>$this->totalNumberOfPages){
			return $this->LastLink();
		}
		
		return ($this->currentPage-1);
	}
	
	/**
	 * Returns a list of pages with links, pagenumber and if it is the current 
	 * page.
	 *
	 * @return ArrayList 
	 */
	public function Pages() {
		if($this->haveNoPages()) {
			return false;
		}
		
		$list = new ArrayList();
		for($idx=1;$idx<=$this->totalNumberOfPages;$idx++) {
			$data = new ArrayData(array());
			$data->setField('PageNumber',$idx);
			if($idx == $this->currentPage ) {
				$data->setField('Current',true);
			} else {
				$data->setField('Current',false);
			}
			
			$data->setField('Link',$idx);
			$list->push($data);	
		}
		return $list;
	}
	
	/**
	 * Returns a url to the next page in the result
	 *
	 * @return string 
	 */
	public function NextLink() {
		if($this->isLastPage() || $this->haveNoPages() ) {
			return false;
		}
		// Out of bounds
		if($this->currentPage<1) {
			return $this->FirstLink();
		}
		return ($this->currentPage+1);
	}
	
	/**
	 * Returns a url to the last page in the result
	 *
	 * @return string 
	 */
	public function LastLink() {
		if($this->haveNoPages()) {
			return false;
		}
		return ($this->totalNumberOfPages);
	}
	
	/**
	 * Are we currently on the first page
	 *
	 * @return bool 
	 */
	protected function isFirstPage() {
		return (bool)($this->currentPage<=1);
	}
	
	/**
	 * Are we currently on the last page?
	 *
	 * @return bool 
	 */
	protected function isLastPage() {
		return (bool)($this->currentPage>=$this->totalNumberOfPages);
	}
	
	/**
	 * Is there only one page of results?
	 *
	 * @return bool 
	 */
	protected function haveNoPages() {
		return (bool)($this->totalNumberOfPages<=1);
	}
	
}

/**
 * This is the extension that decorates the GridFieldPresenter. Since a extension
 * can't be a Viewable data it's split like this.
 * 
 * @see GridField
 * @package sapphire
 */
class GridFieldPaginator_Extension extends Extension {
	
	/**
	 *
	 * @var int
	 */
	protected $paginationLimit;
	
	/**
	 *
	 * @var int
	 */
	protected $totalNumberOfPages = 1;
	
	/**
	 *
	 * @var int
	 */
	protected $currentPage = 1;
	
	/**
	 *
	 * @return string 
	 */
	public function Footer() {
		return new GridFieldPaginator($this->totalNumberOfPages, $this->currentPage);
	}
	
	/**
	 * NOP
	 */
	public function __construct() {}
	
	/**
	 * Set the limit for each page
	 *
	 * @param int $limit
	 * @return GridFieldPaginator_Extension 
	 */
	public function paginationLimit($limit) {
		$this->paginationLimit = $limit;
		return $this;
	}
	
	/**
	 * Filter the list to only contain a pagelength of items
	 * 
	 * @return bool - if the pagination was activated
	 * @see GridFieldPresenter::Items()
	 */
	public function filterList(SS_List $list, $parameters){
		if(!$this->canUsePagination($list)) {
			return false;
		}
		
		$currentPage = $parameters->Request->requestVar('page');
		if(!$currentPage) {
			$currentPage = 1;
		}
		
		$this->totalNumberOfPages = $this->getMaxPagesCount($list);
		
		if($currentPage<1) {
			// Current page is below 1, show nothing and save cpu cycles
			$list->where('1=0');
		} elseif($currentPage > $this->totalNumberOfPages) {
			// current page is over max pages, show nothing and save cpu cycles
			$list->where('1=0');
		} else {
			$offset = ($currentPage-1)*$this->paginationLimit;
			$list->getRange((int)$offset,$this->paginationLimit);
		}
		$this->currentPage = $currentPage;
		
		return true;
	}
	
	/**
	 * Helper function that see if the pagination has been set and that the 
	 * $list can use pagination.
	 *
	 * @param SS_List $list
	 * @return bool
	 */
	protected function canUsePagination(SS_List $list) {
		if(!$this->paginationLimit) {
			return false;
		}
		if(!method_exists($list, 'getRange')) {
			return false;
		}
		if(!method_exists($list, 'limit')){
			return false;
		}
		return true;
	}
	
	/**
	 *
	 * @return int 
	 */
	protected function getMaxPagesCount($list) {
		$list->limit(null);
		$number = $list->count();
		$number = ceil($number/$this->paginationLimit);
		return $number;
	}
}