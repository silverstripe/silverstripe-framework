<?php

/**
 * Implements a "lazy loading" DataObjectSet.
 * Uses {@link DataQuery} to do the actual query generation.
 */
class DataList extends DataObjectSet {
	/**
	 * The DataObject class name that this data list is querying
	 */
	protected $dataClass;
	
	/**
	 * The {@link DataQuery} object responsible for getting this DataObjectSet's records
	 */
	protected $dataQuery;
	
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
	 * Filter this data list by a WHERE clause
	 * @todo Implement array syntax for this.  Perhaps the WHERE clause should be $this->where()?
	 */
	public function filter($filter) {
		$this->dataQuery->filter($filter);
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
	 * Add an join clause to this data list's query.
	 */
	public function join($join) {
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
	 */
	protected function generateItems() {
		$query = $this->dataQuery->query();
		$this->parseQueryLimit($query);
		$rows = $query->execute();
		$results = array();
		foreach($rows as $row) {
			$results[] = $this->createDataObject($row);
		}
		return $results;
	}
		
	/**
	 * Create a data object from the given SQL row
	 */
	protected function createDataObject($row) {
		$defaultClass = $this->dataClass;

		// Failover from RecordClassName to ClassName
		if(empty($row['RecordClassName'])) $row['RecordClassName'] = $row['ClassName'];
		
		// Instantiate the class mentioned in RecordClassName only if it exists, otherwise default to $this->dataClass
		if(class_exists($row['RecordClassName'])) return new $row['RecordClassName']($row);
		else return new $defaultClass($row);
	}
	
	/**
	 * Returns an Iterator for this DataObjectSet.
	 * This function allows you to use DataObjectSets in foreach loops
	 * @return DataObjectSet_Iterator
	 */
	public function getIterator() {
		return new DataObjectSet_Iterator($this->generateItems());
	}

	/**
	 * Convert this DataList to a DataObjectSet.
	 * Useful if you want to push additional records onto the list.
	 */
	public function toDataObjectSet() {
		$array = array();
		foreach($this as $item) $array[] = $item;
		return new DataObjectSet($array);
	}

	/**
	 * Return the number of items in this DataList
	 */
	function Count() {
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
		return $this->filter("\"$key\" = '" . Convert::raw2sql($value) . "'")->First();
	}
	
	
	/**
	 * Filter this list to only contain the given IDs
	 */
	public function byIDs(array $ids) {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		$this->filter("\"$baseClass\".\"ID\" IN (" . implode(',', $ids) .")");

		return $this;
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
			if($id && !isset($has[$id])) $this->add($id);
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
			$this->remove($id);
		}
	}

	/**
	 * Remove every element in this DataList matching the given $filter.
	 */
	function removeByFilter($filter) {
		foreach($this->filter($filter) as $item) {
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
	
	function remove($item) {
		// TO DO: Allow for amendment of this behaviour - for exmaple, we can remove an item from
		// an "ActiveItems" DataList by chaning the status to inactive.

		// By default, we remove an item from a DataList by deleting it.
		if($item instanceof $this->dataClass) $item->delete();

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

}

?>