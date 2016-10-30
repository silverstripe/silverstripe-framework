<?php
/**
 * Implements a "lazy loading" DataObjectSet.
 * Uses {@link DataQuery} to do the actual query generation.
 *
 * DataLists are _immutable_ as far as the query they represent is concerned. When you call a method that
 * alters the query, a new DataList instance is returned, rather than modifying the existing instance
 *
 * When you add or remove an element to the list the query remains the same, but because you have modified
 * the underlying data the contents of the list changes. These are some of those methods:
 *
 *   - add
 *   - addMany
 *   - remove
 *   - removeMany
 *   - removeByID
 *   - removeByFilter
 *   - removeAll
 *
 * Subclasses of DataList may add other methods that have the same effect.
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
	 * Because the returned value is a copy, modifying it won't affect this list's contents. If
	 * you want to alter the data query directly, use the alterDataQuery method
	 *
	 * @return DataQuery
	 */
	public function dataQuery() {
		return clone $this->dataQuery;
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

			$res = call_user_func($callback, $list->dataQuery, $list);
			if ($res) $list->dataQuery = $res;

			return $list;
		}
		else {
			$list = clone $this;
			$list->inAlterDataQueryCall = true;

			try {
				$res = call_user_func($callback, $list->dataQuery, $list);
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
	 * Returns a new DataList instance with the specified query parameter assigned
	 *
	 * @param string|array $keyOrArray Either the single key to set, or an array of key value pairs to set
	 * @param mixed $val If $keyOrArray is not an array, this is the value to set
	 * @return DataList
	 */
	public function setDataQueryParam($keyOrArray, $val = null) {
		$clone = clone $this;

		if(is_array($keyOrArray)) {
			foreach($keyOrArray as $key => $val) {
				$clone->dataQuery->setQueryParam($key, $val);
			}
		}
		else {
			$clone->dataQuery->setQueryParam($keyOrArray, $val);
		}

		return $clone;
	}

	/**
	 * Returns the SQL query that will be used to get this DataList's records.  Good for debugging. :-)
	 *
	 * @param array $parameters Out variable for parameters required for this query
	 * @param string The resulting SQL query (may be paramaterised)
	 */
	public function sql(&$parameters = array()) {
		return $this->dataQuery->query()->sql($parameters);
	}

	/**
	 * Return a new DataList instance with a WHERE clause added to this list's query.
	 *
	 * Supports parameterised queries.
	 * See SQLQuery::addWhere() for syntax examples, although DataList
	 * won't expand multiple method arguments as SQLQuery does.
	 *
	 * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
	 * paramaterised queries
	 * @return DataList
	 */
	public function where($filter) {
		return $this->alterDataQuery(function($query) use ($filter){
			$query->where($filter);
		});
	}

	/**
	 * Return a new DataList instance with a WHERE clause added to this list's query.
	 * All conditions provided in the filter will be joined with an OR
	 *
	 * Supports parameterised queries.
	 * See SQLQuery::addWhere() for syntax examples, although DataList
	 * won't expand multiple method arguments as SQLQuery does.
	 *
	 * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
	 * paramaterised queries
	 * @return DataList
	 */
	public function whereAny($filter) {
		return $this->alterDataQuery(function($query) use ($filter){
			$query->whereAny($filter);
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
	 * Returns true if this DataList can be filtered by the given field.
	 *
	 * @param string $fieldName (May be a related field in dot notation like Member.FirstName)
	 * @return boolean
	 */
	public function canFilterBy($fieldName) {
		$model = singleton($this->dataClass);
		$relations = explode(".", $fieldName);
		// First validate the relationships
		$fieldName = array_pop($relations);
		foreach ($relations as $r) {
			$relationClass = $model->getRelationClass($r);
			if (!$relationClass) return false;
			$model = singleton($relationClass);
			if (!$model) return false;
		}
		// Then check field
		if ($model->hasDatabaseField($fieldName)){
			return true;
		}
		return false;
	}

	/**
	 * Return a new DataList instance with the records returned in this query
	 * restricted by a limit clause.
	 *
	 * @param int $limit
	 * @param int $offset
	 */
	public function limit($limit, $offset = 0) {
		return $this->alterDataQuery(function($query) use ($limit, $offset){
			$query->limit($limit, $offset);
		});
	}

	/**
	 * Return a new DataList instance with distinct records or not
	 *
	 * @param bool $value
	 */
	public function distinct($value) {
		return $this->alterDataQuery(function($query) use ($value){
			$query->distinct($value);
		});
	}

	/**
	 * Return a new DataList instance as a copy of this data list with the sort
	 * order set.
	 *
	 * @see SS_List::sort()
	 * @see SQLQuery::orderby
	 * @example $list = $list->sort('Name'); // default ASC sorting
	 * @example $list = $list->sort('Name DESC'); // DESC sorting
	 * @example $list = $list->sort('Name', 'ASC');
	 * @example $list = $list->sort(array('Name'=>'ASC', 'Age'=>'DESC'));
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

		return $this->alterDataQuery(function($query, $list) use ($sort, $col, $dir){

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
					// Convert column expressions to SQL fragment, while still allowing the passing of raw SQL
					// fragments.
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
	 * @example $list = $list->filter(array('Name'=>'bob', 'Age'=>21)); // bob with the age 21
	 * @example $list = $list->filter(array('Name'=>'bob', 'Age'=>array(21, 43))); // bob with the Age 21 or 43
	 * @example $list = $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
	 *          // aziz with the age 21 or 43 and bob with the Age 21 or 43
	 *
	 * @todo extract the sql from $customQuery into a SQLGenerator class
	 *
	 * @param string|array Escaped SQL statement. If passed as array, all keys and values will be escaped internally
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

		return $this->addFilter($filters);
	}

	/**
	 * Return a new instance of the list with an added filter
	 *
	 * @param array $filterArray
	 */
	public function addFilter($filterArray) {
		$list = $this;

		foreach($filterArray as $field => $value) {
			$fieldArgs = explode(':', $field);
			$field = array_shift($fieldArgs);
			$filterType = array_shift($fieldArgs);
			$modifiers = $fieldArgs;
			$list = $list->applyFilterContext($field, $filterType, $modifiers, $value);
		}

		return $list;
	}

	/**
	 * Return a copy of this list which contains items matching any of these charactaristics.
	 *
	 * @example // only bob in the list
	 *          $list = $list->filterAny('Name', 'bob');
	 *          // SQL: WHERE "Name" = 'bob'
	 * @example // azis or bob in the list
	 *          $list = $list->filterAny('Name', array('aziz', 'bob');
	 *          // SQL: WHERE ("Name" IN ('aziz','bob'))
	 * @example // bob or anyone aged 21 in the list
	 *          $list = $list->filterAny(array('Name'=>'bob, 'Age'=>21));
	 *          // SQL: WHERE ("Name" = 'bob' OR "Age" = '21')
	 * @example // bob or anyone aged 21 or 43 in the list
	 *          $list = $list->filterAny(array('Name'=>'bob, 'Age'=>array(21, 43)));
	 *          // SQL: WHERE ("Name" = 'bob' OR ("Age" IN ('21', '43'))
	 * @example // all bobs, phils or anyone aged 21 or 43 in the list
	 *          $list = $list->filterAny(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
	 *          // SQL: WHERE (("Name" IN ('bob', 'phil')) OR ("Age" IN ('21', '43'))
	 *
	 * @todo extract the sql from this method into a SQLGenerator class
	 *
	 * @param string|array See {@link filter()}
	 * @return DataList
	 */
	public function filterAny() {
		$numberFuncArgs = count(func_get_args());
		$whereArguments = array();

		if($numberFuncArgs == 1 && is_array(func_get_arg(0))) {
			$whereArguments = func_get_arg(0);
		} elseif($numberFuncArgs == 2) {
			$whereArguments[func_get_arg(0)] = func_get_arg(1);
		} else {
			throw new InvalidArgumentException('Incorrect number of arguments passed to filterAny()');
		}

		return $this->alterDataQuery(function($query, $list) use ($whereArguments) {
			$subquery = $query->disjunctiveGroup();

			foreach($whereArguments as $field => $value) {
				$fieldArgs = explode(':',$field);
				$field = array_shift($fieldArgs);
				$filterType = array_shift($fieldArgs);
				$modifiers = $fieldArgs;

				// This is here since PHP 5.3 can't call protected/private methods in a closure.
				$t = singleton($list->dataClass())->dbObject($field);
				if($filterType) {
					$className = "{$filterType}Filter";
				} else {
					$className = 'ExactMatchFilter';
				}
				if(!class_exists($className)){
					$className = 'ExactMatchFilter';
					array_unshift($modifiers, $filterType);
				}
				$t = new $className($field, $value, $modifiers);
				$t->apply($subquery);
			}
		});
	}

	/**
	 * Note that, in the current implementation, the filtered list will be an ArrayList, but this may change in a
	 * future implementation.
	 * @see SS_Filterable::filterByCallback()
	 *
	 * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
	 * @param callable $callback
	 * @return ArrayList (this may change in future implementations)
	 */
	public function filterByCallback($callback) {
		if(!is_callable($callback)) {
			throw new LogicException(sprintf(
				"SS_Filterable::filterByCallback() passed callback must be callable, '%s' given",
				gettype($callback)
			));
		}
		$output = ArrayList::create();
		foreach($this as $item) {
			if(call_user_func($callback, $item, $this)) $output->push($item);
		}
		return $output;
	}

	/**
	 * Translates a {@link Object} relation name to a Database name and apply
	 * the relation join to the query.  Throws an InvalidArgumentException if
	 * the $field doesn't correspond to a relation.
	 *
	 * @throws InvalidArgumentException
	 * @param string $field
	 *
	 * @return string
	 */
	public function getRelationName($field) {
		if(!preg_match('/^[A-Z0-9._]+$/i', $field)) {
			throw new InvalidArgumentException("Bad field expression $field");
		}

		if (!$this->inAlterDataQueryCall) {
			Deprecation::notice(
				'4.0',
				'getRelationName is mutating, and must be called inside an alterDataQuery block'
			);
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
	 * Translates a filter type to a SQL query.
	 *
	 * @param string $field - the fieldname in the db
	 * @param string $filter - example StartsWith, relates to a filtercontext
	 * @param array $modifiers - Modifiers to pass to the filter, ie not,nocase
	 * @param string $value - the value that the filtercontext will use for matching
	 * @todo Deprecated SearchContexts and pull their functionality into the core of the ORM
	 */
	private function applyFilterContext($field, $filter, $modifiers, $value) {
		if($filter) {
			$className = "{$filter}Filter";
		} else {
			$className = 'ExactMatchFilter';
		}

		if(!class_exists($className)) {
			$className = 'ExactMatchFilter';

			array_unshift($modifiers, $filter);
		}

		$t = new $className($field, $value, $modifiers);

		return $this->alterDataQuery(array($t, 'apply'));
	}

	/**
	 * Return a copy of this list which does not contain any items with these charactaristics
	 *
	 * @see SS_List::exclude()
	 * @example $list = $list->exclude('Name', 'bob'); // exclude bob from list
	 * @example $list = $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 * @example $list = $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
	 *          // bob age 21 or 43, phil age 21 or 43 would be excluded
	 *
	 * @todo extract the sql from this method into a SQLGenerator class
	 *
	 * @param string|array Escaped SQL statement. If passed as array, all keys and values will be escaped internally
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

		return $this->alterDataQuery(function($query, $list) use ($whereArguments) {
			$subquery = $query->disjunctiveGroup();

			foreach($whereArguments as $field => $value) {
				$fieldArgs = explode(':', $field);
				$field = array_shift($fieldArgs);
				$filterType = array_shift($fieldArgs);
				$modifiers = $fieldArgs;

				// This is here since PHP 5.3 can't call protected/private methods in a closure.
				$t = singleton($list->dataClass())->dbObject($field);
				if($filterType) {
					$className = "{$filterType}Filter";
				} else {
					$className = 'ExactMatchFilter';
				}
				if(!class_exists($className)){
					$className = 'ExactMatchFilter';
					array_unshift($modifiers, $filterType);
				}
				$t = new $className($field, $value, $modifiers);
				$t->exclude($subquery);
			}
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
	 * @param string $table Table name (unquoted and as escaped SQL)
	 * @param string $onClause Escaped SQL statement, e.g. '"Table1"."ID" = "Table2"."ID"'
	 * @param string $alias - if you want this table to be aliased under another name
	 * @param int $order A numerical index to control the order that joins are added to the query; lower order values
	 * will cause the query to appear first. The default is 20, and joins created automatically by the
	 * ORM have a value of 10.
	 * @param array $parameters Any additional parameters if the join is a parameterised subquery
	 * @return DataList
	 */
	public function innerJoin($table, $onClause, $alias = null, $order = 20, $parameters = array()) {
		return $this->alterDataQuery(function($query) use ($table, $onClause, $alias, $order, $parameters){
			$query->innerJoin($table, $onClause, $alias, $order, $parameters);
		});
	}

	/**
	 * Return a new DataList instance with a left join clause added to this list's query.
	 *
	 * @param string $table Table name (unquoted and as escaped SQL)
	 * @param string $onClause Escaped SQL statement, e.g. '"Table1"."ID" = "Table2"."ID"'
	 * @param string $alias - if you want this table to be aliased under another name
	 * @param int $order A numerical index to control the order that joins are added to the query; lower order values
	 * will cause the query to appear first. The default is 20, and joins created automatically by the
	 * ORM have a value of 10.
	 * @param array $parameters Any additional parameters if the join is a parameterised subquery
	 * @return DataList
	 */
	public function leftJoin($table, $onClause, $alias = null, $order = 20, $parameters = array()) {
		return $this->alterDataQuery(function($query) use ($table, $onClause, $alias, $order, $parameters){
			$query->leftJoin($table, $onClause, $alias, $order, $parameters);
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
	 * @return array
	 */
	public function toNestedArray() {
		$result = array();

		foreach($this as $item) {
			$result[] = $item->toMap();
		}

		return $result;
	}

	/**
	 * Walks the list using the specified callback
	 *
	 * @param callable $callback
	 * @return DataList
	 */
	public function each($callback) {
		foreach($this as $row) {
			$callback($row);
		}

		return $this;
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

		//set query params on the DataObject to tell the lazy loading mechanism the context the object creation context
		$item->setSourceQueryParams($this->dataQuery()->getQueryParams());

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
	 * Find the first DataObject of this DataList where the given key = value
	 *
	 * @param string $key
	 * @param string $value
	 * @return DataObject|null
	 */
	public function find($key, $value) {
		return $this->filter($key, $value)->first();
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
	 * @param array $ids Array of integers
	 * @return DataList
	 */
	public function byIDs(array $ids) {
		return $this->filter('ID', $ids);
	}

	/**
	 * Return the first DataObject with the given ID
	 *
	 * @param int $id
	 * @return DataObject
	 */
	public function byID($id) {
		return $this->filter('ID', $id)->first();
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
	 * Does not respect sort order. Use ->column("ID") to get an ID list with the current sort.
	 *
	 * @return array
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

	public function dbObject($fieldName) {
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
	 * @param mixed $item
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
		$this->removeByID($item->ID);
	}

	/**
	 * Remove an item from this DataList by ID
	 *
	 * @param int $itemID - The primary ID
	 */
	public function removeByID($itemID) {
		$item = $this->byID($itemID);

		if($item) {
			return $item->delete();
		}
	}

	/**
	 * Reverses a list of items.
	 *
	 * @return DataList
	 */
	public function reverse() {
		return $this->alterDataQuery(function($query){
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
		user_error("Can't call DataList::removeDuplicates() because its data comes from a specific query.",
			E_USER_ERROR);
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
