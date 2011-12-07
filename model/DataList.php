<?php
/**
 * Implements a "lazy loading" DataObjectSet.
 * Uses {@link DataQuery} to do the actual query generation.
 * 
 * @package    sapphire
 * @subpackage model
 */
class DataList extends ViewableData implements SS_List {
	/**
	 * The DataObject class name that this data list is querying
	 * 
	 * @var string
	 */
	protected $dataClass;
	
	/**
	 * The {@link DataQuery} object responsible for getting this DataList's records
	 * 
	 * @var DataQuery
	 */
	protected $dataQuery;
	
	/**
	 * The DataModel from which this DataList comes.
	 * 
	 * @var DataModel
	 */
	protected $model;
	
	/**
	 * Synonym of the constructor.  Can be chained with literate methods.
	 * DataList::create("SiteTree")->sort("Title") is legal, but
	 * new DataList("SiteTree")->sort("Title") is not.
	 * 
	 * @param string $dataClass - The DataObject class to query.
	 */
	public static function create($dataClass) {
		return new DataList($dataClass);
	}

	/**
	 * Create a new DataList.
	 * No querying is done on construction, but the initial query schema is set up.
	 *
	 * @param string $dataClass - The DataObject class to query.
	 */
	public function __construct($dataClass) {
		$this->dataClass = $dataClass;
		$this->dataQuery = new DataQuery($this->dataClass);
		parent::__construct();
	}

	/**
	 * Set the DataModel
	 *
	 * @param DataModel $model 
	 */
	public function setModel(DataModel $model) {
		$this->model = $model;
	}
	
	/**
	 * Get the dataClass name for this DataList, ie the DataObject ClassName
	 *
	 * @return string
	 */
	public function dataClass() {
		return $this->dataClass;
	}

	/**
	 * When cloning this object, clone the dataQuery object as well
	 */
	public function __clone() {
		$this->dataQuery = clone $this->dataQuery;
	}
	
	/**
	 * Return the internal {@link DataQuery} object for direct manipulation
	 * 
	 * @return DataQuery
	 */
	public function dataQuery() {
		return $this->dataQuery;
	}

	/**
	 * Returns the SQL query that will be used to get this DataList's records.  Good for debugging. :-)
	 * 
	 * @return SQLQuery
	 */
	public function sql() {
		return $this->dataQuery->query()->sql();
	}
	
	/**
	 * Add a WHERE clause to the query.
	 *
	 * @param string $filter
	 * @return DataList
	 */
	public function where($filter) {
		$this->dataQuery->where($filter);
		return $this;
	}

	/**
	 * Set the sort order of this data list
	 *
	 * @param string $sort
	 * @param string $direction
	 * @return DataList 
	 */
	public function sort($sort, $direction = "ASC") {
		if($direction && strtoupper($direction) != 'ASC') $sort = "$sort $direction";
		$this->dataQuery->sort($sort);
		return $this;
	}
	
	/**
	 * Returns true if this DataList can be sorted by the given field.
	 * 
	 * @param string $fieldName
	 * @return boolean
	 */
	public function canSortBy($fieldName) {
	    return $this->dataQuery()->query()->canSortBy($fieldName);
	}

	/**
	 * Add an join clause to this data list's query.
	 *
	 * @param type $join
	 * @return DataList 
	 * @deprecated 3.0
	 */
	public function join($join) {
		Deprecation::notice('3.0', 'Use innerJoin() or leftJoin() instead.');
		$this->dataQuery->join($join);
		return $this;
	}

	/**
	 * Restrict the records returned in this query by a limit clause
	 * 
	 * @param string $limit
	 */
	public function limit($limit) {
		$this->dataQuery->limit($limit);
		return $this;
	}

	/**
	 * Add an inner join clause to this data list's query.
	 *
	 * @param string $table
	 * @param string $onClause
	 * @param string $alias - if you want this table to be aliased under another name
	 * @return DataList 
	 */
	public function innerJoin($table, $onClause, $alias = null) {
		$this->dataQuery->innerJoin($table, $onClause, $alias);
		return $this;
	}

	/**
	 * Add an left join clause to this data list's query.
	 *
	 * @param string $table
	 * @param string $onClause
	 * @param string $alias - if you want this table to be aliased under another name
	 * @return DataList 
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

	/**
	 * Return this list as an array and every object it as an sub array as well
	 *
	 * @return type 
	 */
	public function toNestedArray() {
		$result = array();
		foreach($this as $item) {
			$result[] = $item->toMap();
		}

		return $result;
	}

	/**
	 * Returns a map of this list
	 *
	 * @param string $keyField - the 'key' field of the result array
	 * @param string $titleField - the value field of the result array
	 * @return SS_Map 
	 */
	public function map($keyField = 'ID', $titleField = 'Title') {
		return new SS_Map($this, $keyField, $titleField);
	}

	/**
	 * Create a DataObject from the given SQL row
	 * 
	 * @param array $row
	 * @return DataObject
	 */
	protected function createDataObject($row) {
		$defaultClass = $this->dataClass;

		// Failover from RecordClassName to ClassName
		if(empty($row['RecordClassName'])) {
			$row['RecordClassName'] = $row['ClassName'];
		}
		
		// Instantiate the class mentioned in RecordClassName only if it exists, otherwise default to $this->dataClass
		if(class_exists($row['RecordClassName'])) {
			$item = new $row['RecordClassName']($row, false, $this->model);
		} else {
			$item = new $defaultClass($row, false, $this->model);
		}
		
		return $item;
	}
	
	/**
	 * Returns an Iterator for this DataList.
	 * This function allows you to use DataLists in foreach loops
	 * 
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->toArray());
	}

	/**
	 * Return the number of items in this DataList
	 * 
	 * @return int
	 */
	public function count() {
		return $this->dataQuery->count();
	}
	
	/**
	 * Return the maximum value of the given field in this DataList
	 *
	 * @param string $fieldName
	 * @return mixed
	 */
	public function max($fieldName) {
	    return $this->dataQuery->max($fieldName);
	}

	/**
	 * Return the minimum value of the given field in this DataList
	 *
	 * @param string $fieldName
	 * @return mixed
	 */
	public function min($fieldName) {
	    return $this->dataQuery->min($fieldName);
	}
	
	/**
	 * Return the average value of the given field in this DataList
	 * 
	 * @param string $fieldName
	 * @return mixed
	 */
	public function avg($fieldName) {
	    return $this->dataQuery->avg($fieldName);
	}

	/**
	 * Return the sum of the values of the given field in this DataList
	 * 
	 * @param string $fieldName
	 * @return mixed
	 */
	public function sum($fieldName) {
	    return $this->dataQuery->sum($fieldName);
	}
	
	
	/**
	 * Returns the first item in this DataList
	 * 
	 * @return DataObject
	 */
	public function first() {
		foreach($this->dataQuery->firstRow()->execute() as $row) {
			return $this->createDataObject($row);
		}
	}

	/**
	 * Returns the last item in this DataList
	 *
	 *  @return DataObject
	 */
	public function last() {
		foreach($this->dataQuery->lastRow()->execute() as $row) {
			return $this->createDataObject($row);
		}
	}
	
	/**
	 * Returns true if this DataList has items
	 * 
	 * @return bool
	 */
	public function exists() {
		return $this->count() > 0;
	}

	/**
	 * Get a sub-range of this dataobjectset as an array
	 *
	 * @param int $offset
	 * @param int $length
	 * @return DataList
	 */
	public function getRange($offset, $length) {
		return $this->limit(array('start' => $offset, 'limit' => $length));
	}
	
	/**
	 * Find the first DataObject of this DataList where the given key = value
	 *
	 * @param string $key
	 * @param string $value
	 * @return DataObject|null
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
	 * Filter this list to only contain the given Primary IDs
	 *
	 * @param array $ids
	 * @return DataList
	 */
	public function byIDs(array $ids) {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		$this->where("\"$baseClass\".\"ID\" IN (" . implode(',', $ids) .")");
		return $this;
	}

	/**
	 * Return the first DataObject with the given ID
	 * 
	 * @param int $id
	 * @return DataObject
	 */
	public function byID($id) {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		return $this->where("\"$baseClass\".\"ID\" = " . (int)$id)->First();
	}
	
	/**
	 * Returns an array of a single field value for all items in the list.
	 *
	 * @param string $colName
	 * @return array
	 */
	public function column($colName = "ID") {
		return $this->dataQuery->column($colName);
	}
	
	// Member altering methods
	
	/**
	 * Sets the ComponentSet to be the given ID list.
	 * Records will be added and deleted as appropriate.
	 * 
	 * @param array $idList List of IDs.
	 */
	public function setByIDList($idList) {
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
	 * 
	 */
	public function getIDList() {
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
	 * 
	 * @param string $relationName
	 * @return HasManyList|ManyManyList
	 */
	public function relation($relationName) {
		$ids = $this->column('ID');
		return singleton($this->dataClass)->$relationName()->forForeignID($ids);
	}

	/**
	 * Add a number of items to the component set.
	 * 
	 * @param array $items Items to add, as either DataObjects or IDs.
	 * @return DataList
	 */
	public function addMany($items) {
		foreach($items as $item) {
			$this->add($item);
		}
		return $this;
	}

	/**
	 * Remove the items from this list with the given IDs
	 * 
	 * @param array $idList
	 * @return DataList
	 */
	public function removeMany($idList) {
		foreach($idList as $id) {
			$this->removeByID($id);
		}
		return $this;
	}

	/**
	 * Remove every element in this DataList matching the given $filter.
	 * 
	 * @param string $filter - a sql type where filter
	 * @return DataList
	 */
	public function removeByFilter($filter) {
		foreach($this->where($filter) as $item) {
			$this->remove($item);
		}
		return $this;
	}

	/**
	 * Remove every element in this DataList.
	 *
	 * @return DataList
	 */
	public function removeAll() {
		foreach($this as $item) {
			$this->remove($item);
		}
		return $this;
	}

	/**
	 * This method are overloaded by HasManyList and ManyMany list to perform more sophisticated 
	 * list manipulation
	 *
	 * @param type $item 
	 */
	public function add($item) {
		// Nothing needs to happen by default
		// TO DO: If a filter is given to this data list then
	}

	/**
	 * Return a new item to add to this DataList.
	 * 
	 * @todo This doesn't factor in filters.
	 */
	public function newObject($initialFields = null) {
		$class = $this->dataClass;
 		return new $class($initialFields, false, $this->model);
	}
	
	/**
	 * Remove this item by deleting it
	 * 
	 * @param DataClass $item 
	 * @todo Allow for amendment of this behaviour - for example, we can remove an item from
	 * an "ActiveItems" DataList by chaning the status to inactive.
	 */
	public function remove($item) {
		// By default, we remove an item from a DataList by deleting it.
		if($item instanceof $this->dataClass) $item->delete();

	}

    /**
     * Remove an item from this DataList by ID
	 * 
	 * @param int $itemID - The primary ID
     */
	public function removeByID($itemID) {
	    $item = $this->byID($itemID);
	    if($item) return $item->delete();
	}

	/**
	 * This method won't function on DataLists due to the specific query that it represent
	 * 
	 * @param mixed $item
	 */
	public function push($item) {
		user_error("Can't call DataList::push() because its data comes from a specific query.", E_USER_ERROR);
	}
	
	/**
	 * This method won't function on DataLists due to the specific query that it represent
	 *
	 * @param mixed $item 
	 */
	public function insertFirst($item) {
		user_error("Can't call DataList::insertFirst() because its data comes from a specific query.", E_USER_ERROR);
	}
	
	/**
	 * This method won't function on DataLists due to the specific query that it represent
	 * 
	 */
	public function shift() {
		user_error("Can't call DataList::shift() because its data comes from a specific query.", E_USER_ERROR);
	}
	
	/**
	 * This method won't function on DataLists due to the specific query that it represent
	 * 
	 */
	public function replace() {
		user_error("Can't call DataList::replace() because its data comes from a specific query.", E_USER_ERROR);
	}
	
	/**
	 * This method won't function on DataLists due to the specific query that it represent
	 *
	 */
	public function merge() {
		user_error("Can't call DataList::merge() because its data comes from a specific query.", E_USER_ERROR);
	}
	
	/**
	 * This method won't function on DataLists due to the specific query that it represent
	 * 
	 */
	public function removeDuplicates() {
		user_error("Can't call DataList::removeDuplicates() because its data comes from a specific query.", E_USER_ERROR);
	}
	
	/**
	 * Returns whether an item with $key exists
	 * 
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($key) {
	    return ($this->getRange($key, 1)->First() != null);
	}

	/**
	 * Returns item stored in list with index $key
	 * 
	 * @param mixed $key
	 * @return DataObject
	 */
	public function offsetGet($key) {
	    return $this->getRange($key, 1)->First();
	}
	
	/**
	 * Set an item with the key in $key
	 * 
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value) {
	    user_error("Can't alter items in a DataList using array-access", E_USER_ERROR);
	}

	/**
	 * Unset an item with the key in $key
	 * 
	 * @param mixed $key
	 */
	public function offsetUnset($key) {
	    user_error("Can't alter items in a DataList using array-access", E_USER_ERROR);
	}	

}
