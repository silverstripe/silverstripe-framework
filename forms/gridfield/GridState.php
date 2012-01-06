<?php
/**
 * This class is a snapshot of the current status of a gridfield. 
 * 
 * It's main use is to be inserted into a Form as a HiddenField
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridState extends HiddenField {

	/** @var GridField */
	protected $grid;

	/**  @var array [GridState_Affector] */
	protected $affectors = array();
	
	/**
	 *
	 * @var array
	 * @todo Do we want these three affectors added by default to every gridfield?
	 */
	protected $defaultAffectorClasses = array('GridState_Pagination', 'GridState_Sorting', 'GridState_Filter');
	
	public static function array_to_object($d) {
		if (is_array($d)) {
			return (object) array_map(array('GridState', 'array_to_object'), $d);
		}
		else {
			return $d;
		}
	}

	/**
	 *
	 * @param GridField $name
	 * @param string $data - json encoded string
	 */
	public function __construct($grid, $value = null) {
		$this->grid = $grid;

		foreach ($this->defaultAffectorClasses as $klass){
			$this->addAffector(Object::create($klass, $this));
		}
		
		if ($value) $this->setValue($value);

		parent::__construct('GridState');
	}
	
	public function addAffector(GridState_Affector $affector, $value = null){
		$this->affectors[Object::get_static(get_class($affector),'name')] = $affector;
		if ($value) $this->setValue($value);
	}
	
	public function removeAffector($name){
		unset($this->affectors[$name]);
	}

	public function setValue($value) {
		if (is_string($value)) $value = json_decode($value);
		foreach ($this->affectors as $affector) $affector->setState($value);
	}
	
	public function __get($name) {
		if(isset($this->affectors[$name])){
			return $this->affectors[$name];
		}
		return parent::__get($name);
	}

	public function getList() {
		return $this->grid->getList();
	}

	public function apply() {
		foreach ($this->affectors as $affector) $affector->apply();
	}

	/** @return string */
	public function Value() {
		$state = new stdClass();
		foreach ($this->affectors as $affector) $affector->getState($state);

		return json_encode($state);
	}

	public function dataValue() {
		return $this->Value();
	}

	public function attrValue() {
		return Convert::raw2att($this->Value());
	}

	public function __toString() {
		return $this->Value();
	}
	
	public function update($values){
		$store = new stdClass();
		$data = $store;
		foreach ($values as $field => $val) {
			$parts = explode('.', $field);
			$store = $data;
			while(count($parts) > 1) {
				$part = array_shift($parts);
				if (!isset($store->$part)) $store->$part = new stdClass();
				$store = $store->$part;
			}
			$part = array_shift($parts);
			$store->$part = is_array($val) ? self::array_to_object($val) : $val;
		}
		$this->setValue($data);
	}

}

abstract class GridState_Affector extends ViewableData {
	static $name = 'Affector';

	protected $state;

	function __construct($state = null) {
		if ($state) $this->state = $state;
		parent::__construct();
	}

	abstract function apply();
	abstract function getState(&$state);
	abstract function setState($state);

	function getList() {
		return $this->state->getList();
	}
}

class GridState_Pagination extends GridState_Affector {
	static $name = 'Pagination';

	protected $Page = 1;
	protected $ItemsPerPage = 50;

	function setState($state) {
		if ($state && isset($state->Pagination)) {
			$paging = $state->Pagination;

			if (isset($paging->Page)) $this->setPage($paging->Page);
			if (isset($paging->ItemsPerPage)) $this->setItemsPerPage($paging->ItemsPerPage);
		}
	}

	/** Duck check to see if list support methods we need to paginate */
	function getListPaginatable() {
		$list = $this->getList();
		// If no list yet, not paginatable
		if (!$list) return false;
		// Check for methods we use
		if(!method_exists($list, 'getRange')) return false;
		if(!method_exists($list, 'limit')) return false;
		// Default it true
		return true;
	}

	function apply() {
		if(!$this->ListPaginatable) return false;

		$page = $this->Page;
		$totalPages = $this->TotalPages;

		if($page<1) {
			// Current page is below 1, show nothing and save cpu cycles
			$this->getList()->where('1=0');
		}
		elseif($page > $totalPages) {
			// current page is over max pages, show nothing and save cpu cycles
			$this->getList()->where('1=0');
		}
		else {
			$offset = ($page-1)*$this->ItemsPerPage;
			$this->getList()->getRange((int)$offset, $this->ItemsPerPage);
		}
	}

	function getTotalPages() {
		$list = $this->getList();
		if (!$list) return 0;

		$list->limit(null);
		$totalItems = $list->count();
		return ceil($totalItems/$this->ItemsPerPage);
	}

	function getState(&$state) {
		$state->Pagination = new stdClass();

		$state->Pagination->Page = $this->Page;
		$state->Pagination->ItemsPerPage = $this->ItemsPerPage;
	}

	function getPage() {
		return $this->Page;
	}

	function setPage($page) {
		$page = (int)$page;
		$this->Page = $page ? $page : 1;
	}

	function getItemsPerPage() {
		return $this->ItemsPerPage;
	}

	function setItemsPerPage($items) {
		$items = (int)$items;
		if ($items && $items > 0) $this->ItemsPerPage = $items;
	}

}

class GridState_Sorting extends GridState_Affector {

	static $name = 'Sorting';

	protected $Order = null;

	function setState($state) {
		if ($state && isset($state->Sorting)) {
			$sorting = $state->Sorting;

			if (isset($sorting->Order)) $this->Order = $sorting->Order;
		}
	}

	function apply() {
		$sortColumns = $this->Order;
		if (!$sortColumns) return;

		foreach($sortColumns as $column => $sortOrder) {
			$resultColumns[] = sprintf("%s %s", Convert::raw2sql($column), Convert::raw2sql($sortOrder));
		}

		$sort = implode(', ', $resultColumns);
		$this->getList()->sort($sort);
	}

	function getState(&$state) {
		$state->Sorting = new stdClass();
		$state->Sorting->Order = $this->Order;
	}

	function setOrder($order) {
		$this->Order = $order;
	}

	function getOrder() {
		return $this->Order;
	}

	function getToggledOrder($col) {
		if (isset($this->Order->$col)) {
			$res = $this->Order->$col;
			return $res == 'asc' ? 'desc' : 'asc';
		}
		else return 'desc';
	}



}

class GridState_Filter extends GridState_Affector {

	static $name = 'Filter';

	protected $Criteria = array();
	
	protected $ResetFilter = 0;

	function setState($state) {
		if ($state) {
		 	if (isset($state->Filter)) {
				$filter = $state->Filter;
				if (isset($filter->Criteria)) {
					$this->Criteria = $filter->Criteria;
				}
				if (isset($filter->ResetFilter)) {
					$this->Criteria = null;
				}
			}
		}
	}

	function apply() {
		$filterColumns = $this->Criteria;
		if (!$filterColumns) return;

		foreach($filterColumns as $column => $value) {
			$columnName = substr($column,9);
			$resultColumns[] = sprintf("LOWER(\"%s\") LIKE LOWER('%%%s%%')", Convert::raw2sql($columnName), Convert::raw2sql($value));
		}
		$where = implode(' AND ', $resultColumns);
		$this->getList()->where($where);
	}

	function getState(&$state) {
		$state->Filter = new stdClass();
		$state->Filter->Criteria = $this->Criteria;
	}

	function setCriteria($criteria) {
		return $this->Criteria = $criteria;
	}
	
	function getCriteria() {
		return $this->Criteria;
	}
	
	function setResetFilter($value){
		$this->ResetFilter = $value;
		DEbug::dump('reset');die;
	}
}