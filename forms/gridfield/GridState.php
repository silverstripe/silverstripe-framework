<?php
/**
 * This class is a snapshot of the current status of a gridfield. It is behaving like a open 
 * container that can be parsed as json and recieve json.
 * 
 * It's main use is to be inserted into HTML as serialized json
 * 
 * @package forms
 */
class GridState extends HiddenField {

	/** @var GridField */
	protected $grid;

	/**  @var [GridState_Affector] */
	protected $affectors = array();

	/**
	 *
	 * @param string $name
	 * @param string $data - json encoded string
	 * @param string $title 
	 */
	public function __construct($grid, $value = null) {
		$this->grid = $grid;

		foreach (ClassInfo::subclassesFor('GridState_Affector') as $klass){
			if ($klass == 'GridState_Affector') continue;

			$name = Object::get_static($klass, 'name');
			$this->affectors[$name] = Object::create($klass, $this);
		}

		if ($value) $this->setValue($value);

		parent::__construct('GridState');
	}

	function setValue($value) {
		if (is_string($value)) $value = json_decode($value);
		foreach ($this->affectors as $affector) $affector->setState($value);
	}

	public function __get($name) {
		return $this->affectors[$name];
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

	function dataValue() {
		return $this->Value();
	}

	function attrValue() {
		return Convert::raw2att($this->Value());
	}

	public function __toString() {
		return $this->Value();
	}
	
	static function array_to_object($d) {
		if (is_array($d)) {
			return (object) array_map(array('GridState', 'array_to_object'), $d);
		}
		else {
			return $d;
		}
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

	function __construct($state) {
		$this->state = $state;
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

	function setState($data) {
		if ($data && isset($data->Pagination)) {
			$paging = $data->Pagination;

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

	function setState($data) {
		if ($data && isset($data->Sorting)) {
			$sorting = $data->Sorting;

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