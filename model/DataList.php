<?php
/**
 * Implements a "lazy loading" DataObjectSet.
 * Uses {@link DataQuery} to do the actual query generation.
 * 
 * @package framework
 * @subpackage model
 */
class DataList extends ViewableData implements SS_List, SS_Filterable, SS_Sortable, SS_Limitable {
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
	 * Returns true if this DataList can be sorted by the given field.
	 * 
	 * @param string $fieldName
	 * @return boolean
	 */
	public function canSortBy($fieldName) {
	    return $this->dataQuery()->query()->canSortBy($fieldName);
	}
	
	/**
	 *
	 * @param string $fieldName
	 * @return boolean
	 */
	public function canFilterBy($fieldName) {
		if($t = singleton($this->dataClass)->hasDatabaseField($fieldName)){
			return true;
		}
		return false;
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
	public function limit($limit, $offset = 0) {
		if(!$limit && !$offset) {
			return $this;
		}
		if($limit && !is_numeric($limit)) {
			Deprecation::notice('3.0', 'Please pass limits as 2 arguments, rather than an array or SQL fragment.');
		}
		$this->dataQuery->limit($limit, $offset);
		return $this;
	}
	
	/**
	 * Set the sort order of this data list
	 *
	 * @see SS_List::sort()
	 * @see SQLQuery::orderby
	 *
	 * @example $list->sort('Name'); // default ASC sorting
	 * @example $list->sort('Name DESC'); // DESC sorting
	 * @example $list->sort('Name', 'ASC');
	 * @example $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
	 *
	 * @return DataList
	 */
	public function sort() {
		if(count(func_get_args()) == 0) {
			return $this;
		}
		
		if(count(func_get_args()) > 2) {
			throw new InvalidArgumentException('This method takes zero, one or two arguments');
		}

		if(count(func_get_args()) == 2) {
			// sort('Name','Desc')
			if(!in_array(strtolower(func_get_arg(1)),array('desc','asc'))){
				user_error('Second argument to sort must be either ASC or DESC');
			}
			
			$this->dataQuery->sort(func_get_arg(0), func_get_arg(1));
		}
		else if(is_string(func_get_arg(0)) && func_get_arg(0)){
			// sort('Name ASC')
			if(stristr(func_get_arg(0), ' asc') || stristr(func_get_arg(0), ' desc')) {
				$this->dataQuery->sort(func_get_arg(0));
			} else {
				$this->dataQuery->sort(func_get_arg(0), 'ASC');
			}
		}
		else if(is_array(func_get_arg(0))) {
			// sort(array('Name'=>'desc'));
			$this->dataQuery->sort(null, null); // wipe the sort
			
			foreach(func_get_arg(0) as $col => $dir) {
				$this->dataQuery->sort($this->getRelationName($col), $dir, false);
			}
		}
		
		return $this;
	}
	
	/**
	 * Filter the list to include items with these charactaristics
	 *
	 * @see SS_List::filter()
	 *
	 * @example $list->filter('Name', 'bob'); // only bob in the list
	 * @example $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob with the Age 21 or 43
	 * @example $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43))); // aziz with the age 21 or 43 and bob with the Age 21 or 43
	 *
	 * @todo extract the sql from $customQuery into a SQLGenerator class
	 *
	 * @return DataList
	 */
	public function filter() {
		$numberFuncArgs = count(func_get_args());
		$whereArguments = array();
		if($numberFuncArgs == 1 && is_array(func_get_arg(0))){
			$whereArguments = func_get_arg(0);
		} elseif($numberFuncArgs == 2) {
			$whereArguments[func_get_arg(0)] = func_get_arg(1);
		} else {
			throw new InvalidArgumentException('Arguments passed to filter() is wrong');
		}

		$SQL_Statements = array();
		foreach($whereArguments as $field => $value) {
			if(is_array($value)) {
				$customQuery = 'IN (\''.implode('\',\'',Convert::raw2sql($value)).'\')';
			} else {
				$customQuery = '= \''.Convert::raw2sql($value).'\'';
			}
			
			if(stristr($field,':')) {
				$fieldArgs = explode(':',$field);
				$field = array_shift($fieldArgs);
				foreach($fieldArgs as $fieldArg){
					$comparisor = $this->applyFilterContext($field, $fieldArg, $value);
				}
			} else {
				$SQL_Statements[] = '"'.Convert::raw2sql($field).'" '.$customQuery;
			}
		}
		if(count($SQL_Statements)) {
			foreach($SQL_Statements as $SQL_Statement){
				$this->dataQuery->where($SQL_Statement);
			}
		}
		return $this;
	}

	/**
	 * Translates a Object relation name to a Database name and apply the relation join to 
	 * the query
	 *
	 * @param string $field
	 * @return string
	 */
	public function getRelationName($field) {
		if(strpos($field,'.') === false) {
			return '"'.$field.'"';
		}
		$relations = explode('.', $field);
		$fieldName = array_pop($relations);
		$relationModelName = $this->dataQuery->applyRelation($field);
		return '"'.$relationModelName.'"."'.$fieldName.'"';
	}

	/**
	 * Translates the comparisator to the sql query
	 *
	 * @param string $field - the fieldname in the db
	 * @param string $comparisators - example StartsWith, relates to a filtercontext
	 * @param string $value - the value that the filtercontext will use for matching
	 * @todo Deprecated SearchContexts and pull their functionality into the core of the ORM
	 */
	private function applyFilterContext($field, $comparisators, $value) {
		$t = singleton($this->dataClass())->dbObject($field);
		$className = "{$comparisators}Filter";
		if(!class_exists($className)){
			throw new InvalidArgumentException('There are no '.$comparisators.' comparisator');
		}
		$t = new $className($field,$value);
		$t->apply($this->dataQuery());
	}
	
	/**
	 * Exclude the list to not contain items with these characteristics
	 *
	 * @see SS_List::exclude()
	 * @example $list->exclude('Name', 'bob'); // exclude bob from list
	 * @example $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 * @example $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); // bob age 21 or 43, phil age 21 or 43 would be excluded
	 *
	 * @todo extract the sql from this method into a SQLGenerator class
	 *
	 * @return DataList
	 */
	public function exclude(){
		$numberFuncArgs = count(func_get_args());
		$whereArguments = array();
		
		if($numberFuncArgs == 1 && is_array(func_get_arg(0))){
			$whereArguments = func_get_arg(0);
		} elseif($numberFuncArgs == 2) {
			$whereArguments[func_get_arg(0)] = func_get_arg(1);
		} else {
			throw new InvalidArgumentException('Arguments passed to exclude() is wrong');
		}

		$SQL_Statements = array();
		foreach($whereArguments as $fieldName => $value) {
			if(is_array($value)){
				$SQL_Statements[] = ('"'.$fieldName.'" NOT IN (\''.implode('\',\'', Convert::raw2sql($value)).'\')');
			} else {
				$SQL_Statements[] = ('"'.$fieldName.'" != \''.Convert::raw2sql($value).'\'');
			}
		}
		$this->dataQuery->whereAny($SQL_Statements);
		return $this;
	}
	
	/**
	 * This method returns a list does not contain any DataObjects that exists in $list
	 * 
	 * It does not return the resulting list, it only adds the constraints on the database to exclude
	 * objects from $list.
	 * The $list passed needs to contain the same dataclass as $this
	 *
	 * @param SS_List $list
	 * @return DataList 
	 * @throws BadMethodCallException
	 */
	public function subtract(SS_List $list) {
		if($this->dataclass() != $list->dataclass()) {
			throw new InvalidArgumentException('The list passed must have the same dataclass as this class');
		}
		
		$newlist = clone $this;
		$newlist->dataQuery->subtract($list->dataQuery());
		
		return $newlist;
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

	public function debug() {
		$val = "<h2>" . $this->class . "</h2><ul>";
		
		foreach($this->toNestedArray() as $item) {
			$val .= "<li style=\"list-style-type: disc; margin-left: 20px\">" . Debug::text($item) . "</li>";
		}
		$val .= "</ul>";
		return $val;
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
		Deprecation::notice("3.0", 'getRange($offset, $length) is deprecated.  Use limit($length, $offset) instead.  Note the new argument order.');
		return $this->limit($length, $offset);
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
		$clone = clone $this;
		
		return $clone->where("\"$baseClass\".\"ID\" = " . (int)$id)->First();
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
	 *     DataList::Create("Group")->relation("Members")
	 * 
	 * @param string $relationName
	 * @return HasManyList|ManyManyList
	 */
	public function relation($relationName) {
		$ids = $this->column('ID');
		return singleton($this->dataClass)->$relationName()->forForeignID($ids);
	}

	function dbObject($fieldName) {
		return singleton($this->dataClass)->dbObject($fieldName);
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
	 * Reverses a list of items.
	 *
	 * @return DataList
	 */
	public function reverse() {
		$this->dataQuery->reverseSort();
		
		return $this;
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
	    return ($this->limit(1,$key)->First() != null);
	}

	/**
	 * Returns item stored in list with index $key
	 * 
	 * @param mixed $key
	 * @return DataObject
	 */
	public function offsetGet($key) {
	    return $this->limit(1, $key)->First();
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
