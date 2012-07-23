<?php
/**
 * Implements a "lazy loading" DataObjectSet.
 * Uses {@link DataQuery} to do the actual query generation.
 *
 * todo 3.1: In 3.0 the below is not currently true for backwards compatible reasons, but code should not rely on current behaviour
 *
 * DataLists have two sets of methods.
 *
 * 1). Selection methods (SS_Filterable, SS_Sortable, SS_Limitable) change the way the list is built, but does not
 *     alter underlying data. There are no external affects from selection methods once this list instance is destructed.
 *
 * 2). Mutation methods change the underlying data. The change persists into the underlying data storage layer.
 *
 * DataLists are _immutable_ as far as selection methods go - they all return new instances of DataList, rather
 * than change the current list.
 *
 * DataLists are _mutable_ as far as mutation methods go - they all act on the existing DataList instance.
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
	public function setDataModel(DataModel $model) {
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
	 * Return a copy of the internal {@link DataQuery} object
	 *
	 * todo 3.1: In 3.0 the below is not currently true for backwards compatible reasons, but code should not rely on this
	 * Because the returned value is a copy, modifying it won't affect this list's contents. If
	 * you want to alter the data query directly, use the alterDataQuery method
	 *
	 * @return DataQuery
	 */
	public function dataQuery() {
		// TODO 3.1: This method potentially mutates self
		return /* clone */ $this->dataQuery;
	}

	/**
	 * @var bool - Indicates if we are in an alterDataQueryCall already, so alterDataQuery can be re-entrant
	 */
	protected $inAlterDataQueryCall = false;

	/**
	 * Return a new DataList instance with the underlying {@link DataQuery} object altered
	 *
	 * If you want to alter the underlying dataQuery for this list, this wrapper method
	 * will ensure that you can do so without mutating the existing List object.
	 *
	 * It clones this list, calls the passed callback function with the dataQuery of the new
	 * list as it's first parameter (and the list as it's second), then returns the list
	 *
	 * Note that this function is re-entrant - it's safe to call this inside a callback passed to
	 * alterDataQuery
	 *
	 * @param $callback
	 * @return DataList
	 */
	public function alterDataQuery($callback) {
		if ($this->inAlterDataQueryCall) {
			$list = $this;

			$res = $callback($list->dataQuery, $list);
			if ($res) $list->dataQuery = $res;

			return $list;
		}
		else {
			$list = clone $this;
			$list->inAlterDataQueryCall = true;

			try {
				$res = $callback($list->dataQuery, $list);
				if ($res) $list->dataQuery = $res;
			}
			catch (Exception $e) {
				$list->inAlterDataQueryCall = false;
				throw $e;
			}

			$list->inAlterDataQueryCall = false;
			return $list;
		}
	}

	/**
	 * In 3.0.0 some methods in DataList mutate their list. We don't want to change that in the 3.0.x
	 * line, but we don't want people relying on it either. This does the same as alterDataQuery, but
	 * _does_ mutate the existing list.
	 *
	 * todo 3.1: All methods that call this need to call alterDataQuery instead
	 */
	protected function alterDataQuery_30($callback) {
		Deprecation::notice('3.1', 'DataList will become immutable in 3.1');

		if ($this->inAlterDataQueryCall) {
			$res = $callback($this->dataQuery, $this);
			if ($res) $this->dataQuery = $res;

			return $this;
		}
		else {
			$this->inAlterDataQueryCall = true;

			try {
				$res = $callback($this->dataQuery, $this);
				if ($res) $this->dataQuery = $res;
			}
			catch (Exception $e) {
				$this->inAlterDataQueryCall = false;
				throw $e;
			}

			$this->inAlterDataQueryCall = false;
			return $this;
		}
	}

	/**
	 * Return a new DataList instance with the underlying {@link DataQuery} object changed
	 *
	 * @param DataQuery $dataQuery
	 * @return DataList
	 */
	public function setDataQuery(DataQuery $dataQuery) {
		$clone = clone $this;
		$clone->dataQuery = $dataQuery;
		return $clone;
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
	 * Return a new DataList instance with a WHERE clause added to this list's query.
	 *
	 * @param string $filter Escaped SQL statement
	 * @return DataList
	 */
	public function where($filter) {
		return $this->alterDataQuery_30(function($query) use ($filter){
			$query->where($filter);
		});
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
	 * Return a new DataList instance with a join clause added to this list's query.
	 *
	 * @param type $join Escaped SQL statement
	 * @return DataList 
	 * @deprecated 3.0
	 */
	public function join($join) {
		Deprecation::notice('3.0', 'Use innerJoin() or leftJoin() instead.');
		return $this->alterDataQuery_30(function($query) use ($join){
			$query->join($join);
		});
	}

	/**
	 * Return a new DataList instance with the records returned in this query restricted by a limit clause
	 * 
	 * @param int $limit
	 * @param int $offset
	 */
	public function limit($limit, $offset = 0) {
		if(!$limit && !$offset) {
			return $this;
		}
		if($limit && !is_numeric($limit)) {
			Deprecation::notice('3.0', 'Please pass limits as 2 arguments, rather than an array or SQL fragment.', Deprecation::SCOPE_GLOBAL);
		}
		return $this->alterDataQuery_30(function($query) use ($limit, $offset){
			$query->limit($limit, $offset);
		});
	}
	
	/**
	 * Return a new DataList instance as a copy of this data list with the sort order set
	 *
	 * @see SS_List::sort()
	 * @see SQLQuery::orderby
	 * @example $list = $list->sort('Name'); // default ASC sorting
	 * @example $list = $list->sort('Name DESC'); // DESC sorting
	 * @example $list = $list->sort('Name', 'ASC');
	 * @example $list = $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
	 *
	 * @param String|array Escaped SQL statement. If passed as array, all keys and values are assumed to be escaped.
	 * @return DataList
	 */
	public function sort() {
		$count = func_num_args();

		if($count == 0) {
			return $this;
		}
		
		if($count > 2) {
			throw new InvalidArgumentException('This method takes zero, one or two arguments');
		}

		$sort = $col = $dir = null;

		if ($count == 2) {
			list($col, $dir) = func_get_args();
		}
		else {
			$sort = func_get_arg(0);
		}

		return $this->alterDataQuery_30(function($query, $list) use ($sort, $col, $dir){

			if ($col) {
				// sort('Name','Desc')
				if(!in_array(strtolower($dir),array('desc','asc'))){
					user_error('Second argument to sort must be either ASC or DESC');
				}

				$query->sort($col, $dir);
			}

			else if(is_string($sort) && $sort){
				// sort('Name ASC')
				if(stristr($sort, ' asc') || stristr($sort, ' desc')) {
					$query->sort($sort);
				} else {
					$query->sort($sort, 'ASC');
				}
			}

			else if(is_array($sort)) {
				// sort(array('Name'=>'desc'));
				$query->sort(null, null); // wipe the sort

				foreach($sort as $col => $dir) {
					// Convert column expressions to SQL fragment, while still allowing the passing of raw SQL fragments.
					try {
						$relCol = $list->getRelationName($col);
					} catch(InvalidArgumentException $e) {
						$relCol = $col;
					}
					$query->sort($relCol, $dir, false);
				}
			}
		});
	}

	/**
	 * Return a copy of this list which only includes items with these charactaristics
	 *
	 * @see SS_List::filter()
	 *
	 * @example $list = $list->filter('Name', 'bob'); // only bob in the list
	 * @example $list = $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 * @example $list = $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
	 * @example $list = $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob with the Age 21 or 43
	 * @example $list = $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43))); // aziz with the age 21 or 43 and bob with the Age 21 or 43
	 *
	 * @todo extract the sql from $customQuery into a SQLGenerator class
	 *
	 * @param string|array Escaped SQL statement. If passed as array, all keys and values are assumed to be escaped.
	 * @return DataList
	 */
	public function filter() {
		// Validate and process arguments
		$arguments = func_get_args();
		switch(sizeof($arguments)) {
			case 1: $filters = $arguments[0]; break;
			case 2: $filters = array($arguments[0] => $arguments[1]); break;
			default:
				throw new InvalidArgumentException('Incorrect number of arguments passed to filter()');
		}
		
		// TODO 3.1: Once addFilter doesn't mutate self, this results in a double clone
		$clone = clone $this;
		$clone->addFilter($filters);
		return $clone;
	}

	/**
	 * Return a new instance of the list with an added filter
	 */
	public function addFilter($filterArray) {
		$SQL_Statements = array();
		foreach($filterArray as $field => $value) {
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
				if($field == 'ID') {
					$field = sprintf('"%s"."ID"', ClassInfo::baseDataClass($this->dataClass));
				} else {
					$field = '"' . Convert::raw2sql($field) . '"';
				}

				$SQL_Statements[] = $field . ' ' . $customQuery;
			}
		}

		if(!count($SQL_Statements)) return $this;

		return $this->alterDataQuery_30(function($query) use ($SQL_Statements){
			foreach($SQL_Statements as $SQL_Statement){
				$query->where($SQL_Statement);
			}
		});
	}

	/**
	 * Filter this DataList by a callback function.
	 * The function will be passed each record of the DataList in turn, and must return true for the record to be included.
	 * Returns the filtered list.
	 * 
	 * Note that, in the current implementation, the filtered list will be an ArrayList, but this may change in a future
	 * implementation.
	 */
	public function filterByCallback($callback) {
		if(!is_callable($callback)) throw new LogicException("DataList::filterByCallback() must be passed something callable.");
		
		$output = new ArrayList;
		foreach($this as $item) {
			if($callback($item)) $output->push($item);
		}
		return $output;
	}

	/**
	 * Translates a Object relation name to a Database name and apply the relation join to 
	 * the query.  Throws an InvalidArgumentException if the $field doesn't correspond to a relation
	 *
	 * @param string $field
	 * @return string
	 */
	public function getRelationName($field) {
		if(!preg_match('/^[A-Z0-9._]+$/i', $field)) {
			throw new InvalidArgumentException("Bad field expression $field");
		}

		if (!$this->inAlterDataQueryCall) {
			Deprecation::notice('3.1', 'getRelationName is mutating, and must be called inside an alterDataQuery block');
		}

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
	 * Return a copy of this list which does not contain any items with these charactaristics
	 *
	 * @see SS_List::exclude()
	 * @example $list = $list->exclude('Name', 'bob'); // exclude bob from list
	 * @example $list = $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 * @example $list = $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); // bob age 21 or 43, phil age 21 or 43 would be excluded
	 *
	 * @todo extract the sql from this method into a SQLGenerator class
	 *
	 * @param string|array Escaped SQL statement. If passed as array, all keys and values are assumed to be escaped.
	 * @return DataList
	 */
	public function exclude() {
		$numberFuncArgs = count(func_get_args());
		$whereArguments = array();

		if($numberFuncArgs == 1 && is_array(func_get_arg(0))) {
			$whereArguments = func_get_arg(0);
		} elseif($numberFuncArgs == 2) {
			$whereArguments[func_get_arg(0)] = func_get_arg(1);
		} else {
			throw new InvalidArgumentException('Incorrect number of arguments passed to exclude()');
		}

		$SQL_Statements = array();
		foreach($whereArguments as $fieldName => $value) {
			if($fieldName == 'ID') {
				$fieldName = sprintf('"%s"."ID"', ClassInfo::baseDataClass($this->dataClass));
			} else {
				$fieldName = '"' . Convert::raw2sql($fieldName) . '"';
			}

			if(is_array($value)){
				$SQL_Statements[] = ($fieldName . ' NOT IN (\''.implode('\',\'', Convert::raw2sql($value)).'\')');
			} else {
				$SQL_Statements[] = ($fieldName . ' != \''.Convert::raw2sql($value).'\'');
			}
		}

		if(!count($SQL_Statements)) return $this;

		return $this->alterDataQuery_30(function($query) use ($SQL_Statements){
			$query->whereAny($SQL_Statements);
		});
	}
	
	/**
	 * This method returns a copy of this list that does not contain any DataObjects that exists in $list
	 * 
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

		return $this->alterDataQuery(function($query) use ($list){
			$query->subtract($list->dataQuery());
		});
	}
	
	/**
	 * Return a new DataList instance with an inner join clause added to this list's query.
	 *
	 * @param string $table Table name (unquoted)
	 * @param string $onClause Escaped SQL statement, e.g. '"Table1"."ID" = "Table2"."ID"'
	 * @param string $alias - if you want this table to be aliased under another name
	 * @return DataList 
	 */
	public function innerJoin($table, $onClause, $alias = null) {
		return $this->alterDataQuery_30(function($query) use ($table, $onClause, $alias){
			$query->innerJoin($table, $onClause, $alias);
		});
	}

	/**
	 * Return a new DataList instance with a left join clause added to this list's query.
	 *
	 * @param string $table Table name (unquoted)
	 * @param string $onClause Escaped SQL statement, e.g. '"Table1"."ID" = "Table2"."ID"'
	 * @param string $alias - if you want this table to be aliased under another name
	 * @return DataList 
	 */
	public function leftJoin($table, $onClause, $alias = null) {
		return $this->alterDataQuery_30(function($query) use ($table, $onClause, $alias){
			$query->leftJoin($table, $onClause, $alias);
		});
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
			$item = Injector::inst()->create($row['RecordClassName'], $row, false, $this->model);
		} else {
			$item = Injector::inst()->create($defaultClass, $row, false, $this->model);
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
		Deprecation::notice("3.0", 'Use limit($length, $offset) instead.  Note the new argument order.');
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
		if($key == 'ID') {
			$baseClass = ClassInfo::baseDataClass($this->dataClass);
			$SQL_col = sprintf('"%s"."%s"', $baseClass, Convert::raw2sql($key));
		} else {
			$SQL_col = sprintf('"%s"', Convert::raw2sql($key));
		}

		// todo 3.1: In 3.1 where won't be mutating, so this can be on $this directly
		$clone = clone $this;
		return $clone->where("$SQL_col = '" . Convert::raw2sql($value) . "'")->First();
	}
	
	/**
	 * Restrict the columns to fetch into this DataList
	 *
	 * @param array $queriedColumns
	 * @return DataList
	 */
	public function setQueriedColumns($queriedColumns) {
		return $this->alterDataQuery(function($query) use ($queriedColumns){
			$query->setQueriedColumns($queriedColumns);
		});
	}

	/**
	 * Filter this list to only contain the given Primary IDs
	 *
	 * @param array $ids Array of integers, will be automatically cast/escaped.
	 * @return DataList
	 */
	public function byIDs(array $ids) {
		$ids = array_map('intval', $ids); // sanitize
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

		// todo 3.1: In 3.1 where won't be mutating, so this can be on $this directly
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
 		return Injector::inst()->create($class, $initialFields, false, $this->model);
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
		return $this->alterDataQuery_30(function($query){
			$query->reverseSort();
		});
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
