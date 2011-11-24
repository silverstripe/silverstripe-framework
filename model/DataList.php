<?php
/**
 * Implements a "lazy loading" DataObjectSet.
 * Uses {@link DataQuery} to do the actual query generation.
 */
class DataList extends ViewableData implements SS_List {
	/**
	 * The DataObject class name that this data list is querying
	 */
	protected $dataClass;
	
	/**
	 * The {@link DataQuery} object responsible for getting this DataList's records
	 */
	protected $dataQuery;
	
	/**
	 * The DataModel from which this DataList comes.
	 */
	protected $model;
	
	/**
	 * Synonym of the constructor.  Can be chained with literate methods.
	 * DataList::create("SiteTree")->sort("Title") is legal, but
	 * new DataList("SiteTree")->sort("Title") is not.
	 */
	static function create($dataClass) {
		return new DataList($dataClass);
	}

	/**
	 * Create a new DataList.
	 * No querying is done on construction, but the initial query schema is set up.
	 * @param $dataClass The DataObject class to query.
	 */
	public function __construct($dataClass) {
		$this->dataClass = $dataClass;
		$this->dataQuery = new DataQuery($this->dataClass);
		parent::__construct();
	}
	
	public function setModel(DataModel $model) {
		$this->model = $model;
	}
	
	public function dataClass() {
		return $this->dataClass;
	}

	/**
	 * Clone this object
	 */
	function __clone() {
		$this->dataQuery = clone $this->dataQuery;
	}
	
	/**
	 * Return the internal {@link DataQuery} object for direct manipulation
	 */
	public function dataQuery() {
		return $this->dataQuery;
	}
	/**
	 * Returns the SQL query that will be used to get this DataList's records.  Good for debugging. :-)
	 */
	public function sql() {
		return $this->dataQuery->query()->sql();
	}
	
	/**
	 * Add a WHERE clause to the query.
	 *
	 * @param string $filter
	 */
	public function where($filter) {
		$this->dataQuery->where($filter);
		return $this;
	}

	/**
	 * Set the sort order of this data list
	 */
	public function sort($sort, $direction = "ASC") {
		if($direction && strtoupper($direction) != 'ASC') $sort = "$sort $direction";
		$this->dataQuery->sort($sort);
		return $this;
	}
	
	/**
	 * Returns true if this DataList can be sorted by the given field.
	 */
	public function canSortBy($field) {
	    return $this->dataQuery()->query()->canSortBy($field);
	}

	/**
	 * Add an join clause to this data list's query.
	 */
	public function join($join) {
		Deprecation::notice('3.0', 'Use innerJoin() or leftJoin() instead.');
		$this->dataQuery->join($join);
		return $this;
	}

	/**
	 * Restrict the records returned in this query by a limit clause
	 */
	public function limit($limit) {
		$this->dataQuery->limit($limit);
		return $this;
	}

	/**
	 * Add an inner join clause to this data list's query.
	 */
	public function innerJoin($table, $onClause, $alias = null) {
		$this->dataQuery->innerJoin($table, $onClause, $alias);
		return $this;
	}

	/**
	 * Add an left join clause to this data list's query.
	 */
	public function leftJoin($table, $onClause, $alias = null) {
		$this->dataQuery->leftJoin($table, $onClause, $alias);
		return $this;
	}

	/**
	 * Return an array of the actual items that this DataList contains at this stage.
	 * This is when the query is actually executed.
	 *
	 * @return array
	 */
	public function toArray() {
		$query = $this->dataQuery->query();
		$rows = $query->execute();
		$results = array();
		foreach($rows as $row) {
			$results[] = $this->createDataObject($row);
		}
		return $results;
	}

	public function toNestedArray() {
		$result = array();

		foreach ($this as $item) {
			$result[] = $item->toMap();
		}

		return $result;
	}

	public function map($keyField = 'ID', $titleField = 'Title') {
		return new SS_Map($this, $keyField, $titleField);
	}

	/**
	 * Create a data object from the given SQL row
	 */
	protected function createDataObject($row) {
		$defaultClass = $this->dataClass;

		// Failover from RecordClassName to ClassName
		if(empty($row['RecordClassName'])) $row['RecordClassName'] = $row['ClassName'];
		
		// Instantiate the class mentioned in RecordClassName only if it exists, otherwise default to $this->dataClass
		if(class_exists($row['RecordClassName'])) $item = new $row['RecordClassName']($row, false, $this->model);
		else $item = new $defaultClass($row, false, $this->model);
		
		return $item;
	}
	
	/**
	 * Returns an Iterator for this DataList.
	 * This function allows you to use DataLists in foreach loops
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->toArray());
	}

	/**
	 * Return the number of items in this DataList
	 */
	function count() {
		return $this->dataQuery->count();
	}
	
	/**
	 * Return the maximum value of the given field in this DataList
	 */
	function Max($field) {
	    return $this->dataQuery->max($field);
	}

	/**
	 * Return the minimum value of the given field in this DataList
	 */
	function Min($field) {
	    return $this->dataQuery->min($field);
	}
	
	/**
	 * Return the average value of the given field in this DataList
	 */
	function Avg($field) {
	    return $this->dataQuery->avg($field);
	}

	/**
	 * Return the sum of the values of the given field in this DataList
	 */
	function Sum($field) {
	    return $this->dataQuery->sum($field);
	}
	
	
	/**
	 * Returns the first item in this DataList
	 */
	function First() {
		foreach($this->dataQuery->firstRow()->execute() as $row) {
			return $this->createDataObject($row);
		}
	}

	/**
	 * Returns the last item in this DataList
	 */
	function Last() {
		foreach($this->dataQuery->lastRow()->execute() as $row) {
			return $this->createDataObject($row);
		}
	}
	
	/**
	 * Returns true if this DataList has items
	 */
	function exists() {
		return $this->count() > 0;
	}

	/**
	 * Get a sub-range of this dataobjectset as an array
	 */
	public function getRange($offset, $length) {
		return $this->limit(array('start' => $offset, 'limit' => $length));
	}
	
	/**
	 * Find an element of this DataList where the given key = value
	 */
	public function find($key, $value) {
		$clone = clone $this;
		
		if($key == 'ID') {
			$baseClass = ClassInfo::baseDataClass($this->dataClass);
			$SQL_col = "\"$baseClass\".\"$key\"";
		} else {
			$SQL_col = "\"$key\"";
		}

		return $clone->where("$SQL_col = '" . Convert::raw2sql($value) . "'")->First();
	}
	
	
	/**
	 * Filter this list to only contain the given IDs
	 */
	public function byIDs(array $ids) {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		$this->where("\"$baseClass\".\"ID\" IN (" . implode(',', $ids) .")");

		return $this;
	}

	/**
	 * Return the item of the given ID
	 */
	public function byID($id) {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		return $this->where("\"$baseClass\".\"ID\" = " . (int)$id)->First();
	}
	
	/**
	 * Return a single column from this DataList.
	 * @param $colNum The DataObject field to return.
	 */
	function column($colName = "ID") {
		return $this->dataQuery->column($colName);
	}
	
	
	// Member altering methods
	/**
	 * Sets the ComponentSet to be the given ID list.
	 * Records will be added and deleted as appropriate.
	 * @param array $idList List of IDs.
	 */
	function setByIDList($idList) {
		$has = array();
		
		// Index current data
		foreach($this->column() as $id) {
		   $has[$id] = true;
		}
		
		// Keep track of items to delete
		$itemsToDelete = $has;
		
		// add items in the list
		// $id is the database ID of the record
		if($idList) foreach($idList as $id) {
			unset($itemsToDelete[$id]);
			if($id && !isset($has[$id])) {
				$this->add($id);
			}
		}

		// Remove any items that haven't been mentioned
		$this->removeMany(array_keys($itemsToDelete));
	}
	
	/**
	 * Returns an array with both the keys and values set to the IDs of the records in this list.
	 */
	function getIDList() {
		$ids = $this->column("ID");
		return $ids ? array_combine($ids, $ids) : array();
	}
	
	/**
	 * Returns a HasManyList or ManyMany list representing the querying of a relation across all
	 * objects in this data list.  For it to work, the relation must be defined on the data class
	 * that you used to create this DataList.
	 * 
	 * Example: Get members from all Groups:
	 * 
	 *     DataObject::get("Group")->relation("Members")
	 */
	
	function relation($relationName) {
		$ids = $this->column('ID');
		return singleton($this->dataClass)->$relationName()->forForeignID($ids);
	}

	/**
	 * Add a number of items to the component set.
	 * @param array $items Items to add, as either DataObjects or IDs.
	 */
	function addMany($items) {
		foreach($items as $item) {
			$this->add($item);
		}
	}

	/**
	 * Remove the items from this list with the given IDs
	 */
	function removeMany($idList) {
		foreach($idList as $id) {
			$this->removeByID($id);
		}
	}

	/**
	 * Remove every element in this DataList matching the given $filter.
	 */
	function removeByFilter($filter) {
		foreach($this->where($filter) as $item) {
			$this->remove($item);
		}
	}

	/**
	 * Remove every element in this DataList.
	 */
	function removeAll() {
		foreach($this as $item) {
			$this->remove($item);
		}
	}

	// These methods are overloaded by HasManyList and ManyMany list to perform
	// more sophisticated list manipulation
	
	function add($item) {
		// Nothing needs to happen by default
		// TO DO: If a filter is given to this data list then
	}

	/**
	 * Return a new item to add to this DataList.
	 * @todo This doesn't factor in filters.
	 */
	function newObject($initialFields = null) {
		$class = $this->dataClass;
 		return new $class($initialFields, false, $this->model);
	}
	
	function remove($item) {
		// TO DO: Allow for amendment of this behaviour - for exmaple, we can remove an item from
		// an "ActiveItems" DataList by chaning the status to inactive.

		// By default, we remove an item from a DataList by deleting it.
		if($item instanceof $this->dataClass) $item->delete();

	}

    /**
     * Remove an item from this DataList by ID
     */
	function removeByID($itemID) {
	    $item = $this->byID($itemID);
	    if($item) return $item->delete();
	}

	// Methods that won't function on DataLists
	
	function push($item) {
		user_error("Can't call DataList::push() because its data comes from a specific query.", E_USER_ERROR);
	}
	function insertFirst($item) {
		user_error("Can't call DataList::insertFirst() because its data comes from a specific query.", E_USER_ERROR);
	}
	function shift() {
		user_error("Can't call DataList::shift() because its data comes from a specific query.", E_USER_ERROR);
	}
	function replace() {
		user_error("Can't call DataList::replace() because its data comes from a specific query.", E_USER_ERROR);
	}
	function merge() {
		user_error("Can't call DataList::merge() because its data comes from a specific query.", E_USER_ERROR);
	}
	function removeDuplicates() {
		user_error("Can't call DataList::removeDuplicates() because its data comes from a specific query.", E_USER_ERROR);
	}
	
	/**
	 * Necessary for interface ArrayAccess. Returns whether an item with $key exists
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($key) {
	    return ($this->getRange($key, 1)->First() != null);
	}

	/**
	 * Necessary for interface ArrayAccess. Returns item stored in array with index $key
	 * @param mixed $key
	 * @return DataObject
	 */
	public function offsetGet($key) {
	    return $this->getRange($key, 1)->First();
	}
	
	/**
	 * Necessary for interface ArrayAccess. Set an item with the key in $key
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value) {
	    throw new Exception("Can't alter items in a DataList using array-access");
	}

	/**
	 * Necessary for interface ArrayAccess. Unset an item with the key in $key
	 * @param mixed $key
	 */
	public function offsetUnset($key) {
	    throw new Exception("Can't alter items in a DataList using array-access");
	}	

}
