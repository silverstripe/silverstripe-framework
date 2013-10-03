<?php

/**
 * An object representing a query of data from the DataObject's supporting database.
 * Acts as a wrapper over {@link SQLQuery} and performs all of the query generation.
 * Used extensively by {@link DataList}.
 *
 * Unlike DataList, modifiers on DataQuery modify the object rather than returning a clone.
 * DataList is immutable, DataQuery is mutable.
 *
 * @subpackage model
 * @package framework
 */
class DataQuery {
	
	/**
	 * @var string
	 */
	protected $dataClass;
	
	/**
	 * @var SQLQuery
	 */
	protected $query;
	
	/**
	 * @var array
	 */
	protected $collidingFields = array();

	private $queriedColumns = null;
	
	/**
	 * @var Boolean
	 */
	private $queryFinalised = false;
	
	// TODO: replace subclass_access with this
	protected $querySubclasses = true;
	// TODO: replace restrictclasses with this
	protected $filterByClassName = true;
	
	/**
	 * Create a new DataQuery.
	 *
	 * @param String The name of the DataObject class that you wish to query
	 */
	public function __construct($dataClass) {
		$this->dataClass = $dataClass;
		$this->initialiseQuery();
	}
	
	/**
	 * Clone this object
	 */
	public function __clone() {
		$this->query = clone $this->query;
	}
	
	/**
	 * Return the {@link DataObject} class that is being queried.
	 */
	public function dataClass() {
		return $this->dataClass;
	}

	/**
	 * Return the {@link SQLQuery} object that represents the current query; note that it will
	 * be a clone of the object.
	 */
	public function query() {
		return $this->getFinalisedQuery();
	}
	
	
	/**
	 * Remove a filter from the query
	 */
	public function removeFilterOn($fieldExpression) {
		$matched = false;

		$where = $this->query->getWhere();
		foreach($where as $i => $clause) {
			if(strpos($clause, $fieldExpression) !== false) {
				unset($where[$i]);
				$matched = true;
			}
		}

		// set the entire where clause back, but clear the original one first
		if($matched) {
			$this->query->setWhere($where);
		} else {
			throw new InvalidArgumentException("Couldn't find $fieldExpression in the query filter.");
		}

		return $this;
	}
	
	/**
	 * Set up the simplest initial query
	 */
	public function initialiseQuery() {
		// Get the tables to join to.
		// Don't get any subclass tables - let lazy loading do that.
		$tableClasses = ClassInfo::ancestry($this->dataClass, true);
		
		// Error checking
		if(!$tableClasses) {
			if(!SS_ClassLoader::instance()->hasManifest()) {
				user_error("DataObjects have been requested before the manifest is loaded. Please ensure you are not"
					. " querying the database in _config.php.", E_USER_ERROR);
			} else {
				user_error("DataList::create Can't find data classes (classes linked to tables) for"
					. " $this->dataClass. Please ensure you run dev/build after creating a new DataObject.",
					E_USER_ERROR);
			}
		}

		$baseClass = array_shift($tableClasses);

		// Build our intial query
		$this->query = new SQLQuery(array());
		$this->query->setDistinct(true);
		
		if($sort = singleton($this->dataClass)->stat('default_sort')) {
			$this->sort($sort);
		}

		$this->query->setFrom("\"$baseClass\"");

		$obj = Injector::inst()->get($baseClass);
		$obj->extend('augmentDataQueryCreation', $this->query, $this);
	}

	public function setQueriedColumns($queriedColumns) {
		$this->queriedColumns = $queriedColumns;
	}

	/**
	 * Ensure that the query is ready to execute.
	 *
	 * @return SQLQuery
	 */
	public function getFinalisedQuery($queriedColumns = null) {
		if(!$queriedColumns) $queriedColumns = $this->queriedColumns;
		if($queriedColumns) {
			$queriedColumns = array_merge($queriedColumns, array('Created', 'LastEdited', 'ClassName'));
		}

		$query = clone $this->query;

		// Generate the list of tables to iterate over and the list of columns required by any existing where clauses.
		// This second step is skipped if we're fetching the whole dataobject as any required columns will get selected
		// regardless.
		if($queriedColumns) {
			$tableClasses = ClassInfo::dataClassesFor($this->dataClass);

			foreach ($query->getWhere() as $where) {
				// Check for just the column, in the form '"Column" = ?' and the form '"Table"."Column"' = ?
				if (preg_match('/^"([^"]+)"/', $where, $matches) ||
					preg_match('/^"([^"]+)"\."[^"]+"/', $where, $matches)) {
					if (!in_array($matches[1], $queriedColumns)) $queriedColumns[] = $matches[1];
				}
			}
		}
		else $tableClasses = ClassInfo::ancestry($this->dataClass, true);

		$tableNames = array_keys($tableClasses);
		$baseClass = $tableNames[0];

		// Iterate over the tables and check what we need to select from them. If any selects are made (or the table is
		// required for a select)
		foreach($tableClasses as $tableClass) {
			$joinTable = false;

			// If queriedColumns is set, then check if any of the fields are in this table.
			if ($queriedColumns) {
				$tableFields = DataObject::database_fields($tableClass);
				$selectColumns = array();
				// Look through columns specifically requested in query (or where clause)
				foreach ($queriedColumns as $queriedColumn) {
					if (array_key_exists($queriedColumn, $tableFields)) {
						$selectColumns[] = $queriedColumn;
					}
				}

				$this->selectColumnsFromTable($query, $tableClass, $selectColumns);
				if ($selectColumns && $tableClass != $baseClass) {
					$joinTable = true;
				}
			} else {
				$this->selectColumnsFromTable($query, $tableClass);
				if ($tableClass != $baseClass) $joinTable = true;
			}

			if ($joinTable) {
				$query->addLeftJoin($tableClass, "\"$tableClass\".\"ID\" = \"$baseClass\".\"ID\"", $tableClass, 10) ;
			}
		}
		
		// Resolve colliding fields
		if($this->collidingFields) {
			foreach($this->collidingFields as $k => $collisions) {
				$caseClauses = array();
				foreach($collisions as $collision) {
					if(preg_match('/^"([^"]+)"/', $collision, $matches)) {
						$collisionBase = $matches[1];
						$collisionClasses = ClassInfo::subclassesFor($collisionBase);
						$collisionClasses = array_map(array(DB::getConn(), 'prepStringForDB'), $collisionClasses);
						$caseClauses[] = "WHEN \"$baseClass\".\"ClassName\" IN ("
							. implode(", ", $collisionClasses) . ") THEN $collision";
					} else {
						user_error("Bad collision item '$collision'", E_USER_WARNING);
					}
				}
				$query->selectField("CASE " . implode( " ", $caseClauses) . " ELSE NULL END", $k);
			}
		}


		if($this->filterByClassName) {
			// If querying the base class, don't bother filtering on class name
			if($this->dataClass != $baseClass) {
				// Get the ClassName values to filter to
				$classNames = ClassInfo::subclassesFor($this->dataClass);
				if(!$classNames) user_error("DataList::create() Can't find data sub-classes for '$callerClass'");
				$classNames = array_map(array(DB::getConn(), 'prepStringForDB'), $classNames);
				$query->addWhere("\"$baseClass\".\"ClassName\" IN (" . implode(",", $classNames) . ")");
			}
		}

		$query->selectField("\"$baseClass\".\"ID\"", "ID");
		$query->selectField("CASE WHEN \"$baseClass\".\"ClassName\" IS NOT NULL THEN \"$baseClass\".\"ClassName\""
			. " ELSE ".DB::getConn()->prepStringForDB($baseClass)." END", "RecordClassName");

		// TODO: Versioned, Translatable, SiteTreeSubsites, etc, could probably be better implemented as subclasses
		// of DataQuery

		$obj = Injector::inst()->get($this->dataClass);
		$obj->extend('augmentSQL', $query, $this);

		$this->ensureSelectContainsOrderbyColumns($query);

		return $query;
	}

	/**
	 * Ensure that if a query has an order by clause, those columns are present in the select.
	 * 
	 * @param SQLQuery $query
	 * @return null
	 */
	protected function ensureSelectContainsOrderbyColumns($query, $originalSelect = array()) {
		$tableClasses = ClassInfo::dataClassesFor($this->dataClass);
		$baseClass = array_shift($tableClasses);

		if($orderby = $query->getOrderBy()) {
			$newOrderby = array();
			foreach($orderby as $k => $dir) {
				$newOrderby[$k] = $dir;
				
				// don't touch functions in the ORDER BY or public function calls
				// selected as fields
				if(strpos($k, '(') !== false) continue;

				$col = str_replace('"', '', trim($k));
				$parts = explode('.', $col);

				// Pull through SortColumn references from the originalSelect variables
				if(preg_match('/_SortColumn/', $col)) {
					if(isset($originalSelect[$col])) $query->selectField($originalSelect[$col], $col);
					continue;
				}
				
				if(count($parts) == 1) {
					$databaseFields = DataObject::database_fields($baseClass);
	
					// database_fields() doesn't return ID, so we need to 
					// manually add it here
					$databaseFields['ID'] = true;
				
					if(isset($databaseFields[$parts[0]])) {
						$qualCol = "\"$baseClass\".\"{$parts[0]}\"";
					} else {
						$qualCol = "\"$parts[0]\"";
					}
						
					// remove original sort
					unset($newOrderby[$k]);
					// add new columns sort
					$newOrderby[$qualCol] = $dir;
							
					// To-do: Remove this if block once SQLQuery::$select has been refactored to store getSelect()
					// format internally; then this check can be part of selectField()
					$selects = $query->getSelect();
					if(!isset($selects[$col]) && !in_array($qualCol, $selects)) {
						$query->selectField($qualCol);
					}
				} else {
					$qualCol = '"' . implode('"."', $parts) . '"';
					
					// To-do: Remove this if block once SQLQuery::$select has been refactored to store getSelect()
					// format internally; then this check can be part of selectField()
					if(!in_array($qualCol, $query->getSelect())) {
						$query->selectField($qualCol);
					}
				}
			}

			$query->setOrderBy($newOrderby);
		}
	}

	/**
	 * Execute the query and return the result as {@link Query} object.
	 */
	public function execute() {
		return $this->getFinalisedQuery()->execute();
	}

	/**
	 * Return this query's SQL
	 */
	public function sql() {
		return $this->getFinalisedQuery()->sql();
	}

	/**
	 * Return the number of records in this query.
	 * Note that this will issue a separate SELECT COUNT() query.
	 */
	public function count() {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		return $this->getFinalisedQuery()->count("DISTINCT \"$baseClass\".\"ID\"");
	}

	/**
	 * Return the maximum value of the given field in this DataList
	 * 
	 * @param String $field Unquoted database column name (will be escaped automatically)
	 */
	public function max($field) {
		return $this->aggregate(sprintf('MAX("%s")', Convert::raw2sql($field)));
	}

	/**
	 * Return the minimum value of the given field in this DataList
	 * 
	 * @param String $field Unquoted database column name (will be escaped automatically)
	 */
	public function min($field) {
		return $this->aggregate(sprintf('MIN("%s")', Convert::raw2sql($field)));
	}
	
	/**
	 * Return the average value of the given field in this DataList
	 * 
	 * @param String $field Unquoted database column name (will be escaped automatically)
	 */
	public function avg($field) {
		return $this->aggregate(sprintf('AVG("%s")', Convert::raw2sql($field)));
	}

	/**
	 * Return the sum of the values of the given field in this DataList
	 * 
	 * @param String $field Unquoted database column name (will be escaped automatically)
	 */
	public function sum($field) {
		return $this->aggregate(sprintf('SUM("%s")', Convert::raw2sql($field)));
	}
	
	/**
	 * Runs a raw aggregate expression.  Please handle escaping yourself
	 */
	public function aggregate($expression) {
		return $this->getFinalisedQuery()->aggregate($expression)->execute()->value();
	}

	/**
	 * Return the first row that would be returned by this full DataQuery
	 * Note that this will issue a separate SELECT ... LIMIT 1 query.
	 */
	public function firstRow() {
		return $this->getFinalisedQuery()->firstRow();
	}

	/**
	 * Return the last row that would be returned by this full DataQuery
	 * Note that this will issue a separate SELECT ... LIMIT query.
	 */
	public function lastRow() {
		return $this->getFinalisedQuery()->lastRow();
	}

	/**
	 * Update the SELECT clause of the query with the columns from the given table
	 */
	protected function selectColumnsFromTable(SQLQuery &$query, $tableClass, $columns = null) {
		// Add SQL for multi-value fields
		$databaseFields = DataObject::database_fields($tableClass);
		$compositeFields = DataObject::composite_fields($tableClass, false);
		if($databaseFields) foreach($databaseFields as $k => $v) {
			if((is_null($columns) || in_array($k, $columns)) && !isset($compositeFields[$k])) {
				// Update $collidingFields if necessary
				if($expressionForField = $query->expressionForField($k)) {
					if(!isset($this->collidingFields[$k])) $this->collidingFields[$k] = array($expressionForField);
					$this->collidingFields[$k][] = "\"$tableClass\".\"$k\"";
				
				} else {
					$query->selectField("\"$tableClass\".\"$k\"", $k);
				}
			}
		}
		if($compositeFields) foreach($compositeFields as $k => $v) {
			if((is_null($columns) || in_array($k, $columns)) && $v) {
				$dbO = Object::create_from_string($v, $k);
				$dbO->addToQuery($query);
			}
		}
	}
	
	/**
	 * Set the GROUP BY clause of this query.
	 * 
	 * @param String $groupby Escaped SQL statement
	 */
	public function groupby($groupby) {
		$this->query->addGroupBy($groupby);
		return $this;
	}
	
	/**
	 * Set the HAVING clause of this query.
	 * 
	 * @param String $having Escaped SQL statement
	 */
	public function having($having) {
		$this->query->addHaving($having);
		return $this;
	}

	/**
	 * Create a disjunctive subgroup.
	 *
	 * That is a subgroup joined by OR
	 *
	 * @return DataQuery_SubGroup
	 */
	public function disjunctiveGroup() {
		return new DataQuery_SubGroup($this, 'OR');
	}

	/**
	 * Create a conjunctive subgroup
	 *
	 * That is a subgroup joined by AND
	 *
	 * @return DataQuery_SubGroup
	 */
	public function conjunctiveGroup() {
		return new DataQuery_SubGroup($this, 'AND');
	}

	/**
	 * Set the WHERE clause of this query.
	 * There are two different ways of doing this:
	 *
	 * <code>
	 *  // the entire predicate as a single string
	 *  $query->where("\"Column\" = 'Value'");
	 *
	 *  // multiple predicates as an array
	 *  $query->where(array("\"Column\" = 'Value'", "\"Column\" != 'Value'"));
	 * </code>
	 *
	 * @param string|array $where Predicate(s) to set, as escaped SQL statements.
	 */
	public function where($filter) {
		if($filter) {
			$this->query->addWhere($filter);
		}
		return $this;
	}

	/**
	 * Set a WHERE with OR.
	 * 
	 * @example $dataQuery->whereAny(array("\"Monkey\" = 'Chimp'", "\"Color\" = 'Brown'"));
	 * @see where()
	 *
	 * @param array $filter Escaped SQL statement.
	 * @return DataQuery
	 */
	public function whereAny($filter) {
		if($filter) {
			$this->query->addWhereAny($filter);
		}
		return $this;
	}
	
	/**
	 * Set the ORDER BY clause of this query
	 *
	 * @see SQLQuery::orderby()
	 *
	 * @param String $sort Column to sort on (escaped SQL statement)
	 * @param String $direction Direction ("ASC" or "DESC", escaped SQL statement)
	 * @param Boolean $clear Clear existing values
	 * @return DataQuery
	 */
	public function sort($sort = null, $direction = null, $clear = true) {
		if($clear) {
			$this->query->setOrderBy($sort, $direction);
		} else {
			$this->query->addOrderBy($sort, $direction);
		}
			
		return $this;
	}
	
	/**
	 * Reverse order by clause
	 *
	 * @return DataQuery
	 */
	public function reverseSort() {
		$this->query->reverseOrderBy();
		return $this;
	}
	
	/**
	 * Set the limit of this query.
	 * 
	 * @param int $limit
	 * @param int $offset
	 */
	public function limit($limit, $offset = 0) {
		$this->query->setLimit($limit, $offset);
		return $this;
	}

	/**
	 * Add an INNER JOIN clause to this query.
	 * 
	 * @param String $table The unquoted table name to join to.
	 * @param String $onClause The filter for the join (escaped SQL statement)
	 * @param String $alias An optional alias name (unquoted)
	 */
	public function innerJoin($table, $onClause, $alias = null) {
		if($table) {
			$this->query->addInnerJoin($table, $onClause, $alias);
		}
		return $this;
	}

	/**
	 * Add a LEFT JOIN clause to this query.
	 * 
	 * @param String $table The unquoted table to join to.
	 * @param String $onClause The filter for the join (escaped SQL statement).
	 * @param String $alias An optional alias name (unquoted)
	 */
	public function leftJoin($table, $onClause, $alias = null) {
		if($table) {
			$this->query->addLeftJoin($table, $onClause, $alias);
		}
		return $this;
	}

	/**
	 * Traverse the relationship fields, and add the table
	 * mappings to the query object state. This has to be called
	 * in any overloaded {@link SearchFilter->apply()} methods manually.
	 * 
	 * @param String|array $relation The array/dot-syntax relation to follow
	 * @return The model class of the related item
	 */
	public function applyRelation($relation) {
		// NO-OP
		if(!$relation) return $this->dataClass;
		
		if(is_string($relation)) $relation = explode(".", $relation);

		$modelClass = $this->dataClass;
		
		foreach($relation as $rel) {
			$model = singleton($modelClass);
			if ($component = $model->has_one($rel)) {
				if(!$this->query->isJoinedTo($component)) {
					$foreignKey = $model->getReverseAssociation($component);
					$this->query->addLeftJoin($component,
						"\"$component\".\"ID\" = \"{$modelClass}\".\"{$foreignKey}ID\"");
				
					/**
					 * add join clause to the component's ancestry classes so that the search filter could search on
					 * its ancestor fields.
					 */
					$ancestry = ClassInfo::ancestry($component, true);
					if(!empty($ancestry)){
						$ancestry = array_reverse($ancestry);
						foreach($ancestry as $ancestor){
							if($ancestor != $component){
								$this->query->addInnerJoin($ancestor, "\"$component\".\"ID\" = \"$ancestor\".\"ID\"");
							}
						}
					}
				}
				$modelClass = $component;

			} elseif ($component = $model->has_many($rel)) {
				if(!$this->query->isJoinedTo($component)) {
					$ancestry = $model->getClassAncestry();
					$foreignKey = $model->getRemoteJoinField($rel);
					$this->query->addLeftJoin($component,
						"\"$component\".\"{$foreignKey}\" = \"{$ancestry[0]}\".\"ID\"");
					/**
					 * add join clause to the component's ancestry classes so that the search filter could search on
					 * its ancestor fields.
					 */
					$ancestry = ClassInfo::ancestry($component, true);
					if(!empty($ancestry)){
						$ancestry = array_reverse($ancestry);
						foreach($ancestry as $ancestor){
							if($ancestor != $component){
								$this->query->addInnerJoin($ancestor, "\"$component\".\"ID\" = \"$ancestor\".\"ID\"");
							}
						}
					}
				}
				$modelClass = $component;

			} elseif ($component = $model->many_many($rel)) {
				list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
				$parentBaseClass = ClassInfo::baseDataClass($parentClass);
				$componentBaseClass = ClassInfo::baseDataClass($componentClass);
				$this->query->addInnerJoin($relationTable,
					"\"$relationTable\".\"$parentField\" = \"$parentBaseClass\".\"ID\"");
				$this->query->addLeftJoin($componentBaseClass,
					"\"$relationTable\".\"$componentField\" = \"$componentBaseClass\".\"ID\"");
				if(ClassInfo::hasTable($componentClass)) {
					$this->query->addLeftJoin($componentClass,
						"\"$relationTable\".\"$componentField\" = \"$componentClass\".\"ID\"");
				}
				$modelClass = $componentClass;

			}
		}
		
		return $modelClass;
	}
	
	/**
	 * Removes the result of query from this query.
	 * 
	 * @param DataQuery $subtractQuery
	 * @param string $field 
	 */
	public function subtract(DataQuery $subtractQuery, $field='ID') {
		$fieldExpression = $subtractQuery->expressionForField($field);
		$subSelect = $subtractQuery->getFinalisedQuery();
		$subSelect->setSelect(array());
		$subSelect->selectField($fieldExpression, $field);
		$subSelect->setOrderBy(null);
		$this->where($this->expressionForField($field).' NOT IN ('.$subSelect->sql().')');

		return $this;
	}

	/**
	 * Select the given fields from the given table.
	 * 
	 * @param String $table Unquoted table name (will be escaped automatically)
	 * @param Array $fields Database column names (will be escaped automatically)
	 */
	public function selectFromTable($table, $fields) {
		$table = Convert::raw2sql($table);
		$fieldExpressions = array_map(create_function('$item', 
			"return '\"$table\".\"' . Convert::raw2sql(\$item) . '\"';"), $fields);
		
		$this->query->setSelect($fieldExpressions);

		return $this;
	}

	/**
	 * Query the given field column from the database and return as an array.
	 * 
	 * @param String $field See {@link expressionForField()}.
	 */
	public function column($field = 'ID') {
		$fieldExpression = $this->expressionForField($field);
		$query = $this->getFinalisedQuery(array($field));
		$originalSelect = $query->getSelect();
		$query->setSelect(array());
		$query->selectField($fieldExpression, $field);
		$this->ensureSelectContainsOrderbyColumns($query, $originalSelect);

		return $query->execute()->column($field);
	}
	
	/**
	 * @param  String $field Select statement identifier, either the unquoted column name,
	 * the full composite SQL statement, or the alias set through {@link SQLQuery->selectField()}.
	 * @return String The expression used to query this field via this DataQuery
	 */
	protected function expressionForField($field) {
		
		// Prepare query object for selecting this field
		$query = $this->getFinalisedQuery(array($field));
		
		// Allow query to define the expression for this field
		$expression = $query->expressionForField($field);
		if(!empty($expression)) return $expression;
		
		// Special case for ID, if not provided
		if($field === 'ID') {
			$baseClass = ClassInfo::baseDataClass($this->dataClass);
			return "\"$baseClass\".\"ID\"";	
		}
	}

	/**
	 * Select the given field expressions.
	 * 
	 * @param $fieldExpression String The field to select (escaped SQL statement)
	 * @param $alias String The alias of that field (escaped SQL statement)
	 */
	protected function selectField($fieldExpression, $alias = null) {
		$this->query->selectField($fieldExpression, $alias);
	}

	//// QUERY PARAMS

	/**
	 * An arbitrary store of query parameters that can be used by decorators.
	 * @todo This will probably be made obsolete if we have subclasses of DataList and/or DataQuery.
	 */
	private $queryParams;
	
	/**
	 * Set an arbitrary query parameter, that can be used by decorators to add additional meta-data to the query.
	 * It's expected that the $key will be namespaced, e.g, 'Versioned.stage' instead of just 'stage'.
	 */
	public function setQueryParam($key, $value) {
		$this->queryParams[$key] = $value;
	}
	
	/**
	 * Set an arbitrary query parameter, that can be used by decorators to add additional meta-data to the query.
	 */
	public function getQueryParam($key) {
		if(isset($this->queryParams[$key])) return $this->queryParams[$key];
		else return null;
	}

	/**
	 * Returns all query parameters
	 * @return array query parameters array
	 */
	public function getQueryParams() {
		return $this->queryParams;
	}
}

/**
 * Represents a subgroup inside a WHERE clause in a {@link DataQuery}
 *
 * Stores the clauses for the subgroup inside a specific {@link SQLQuery} 
 * object.
 *
 * All non-where methods call their DataQuery versions, which uses the base
 * query object.
 *
 * @package framework
 */
class DataQuery_SubGroup extends DataQuery {

	protected $whereQuery;

	public function __construct(DataQuery $base, $connective) {
		$this->dataClass = $base->dataClass;
		$this->query = $base->query;
		$this->whereQuery = new SQLQuery;
		$this->whereQuery->setConnective($connective);

		$base->where($this);
	}

	/**
	 * Set the WHERE clause of this query.
	 * There are two different ways of doing this:
	 *
	 * <code>
	 *  // the entire predicate as a single string
	 *  $query->where("\"Column\" = 'Value'");
	 *
	 *  // multiple predicates as an array
	 *  $query->where(array("\"Column\" = 'Value'", "\"Column\" != 'Value'"));
	 * </code>
	 *
	 * @param string|array $where Predicate(s) to set, as escaped SQL statements.
	 */
	public function where($filter) {
		if($filter) {
			$this->whereQuery->addWhere($filter);
		}

		return $this;
	}

	/**
	 * Set a WHERE with OR.
	 * 
	 * @example $dataQuery->whereAny(array("\"Monkey\" = 'Chimp'", "\"Color\" = 'Brown'"));
	 * @see where()
	 *
	 * @param array $filter Escaped SQL statement.
	 * @return DataQuery
	 */
	public function whereAny($filter) {
		if($filter) {
			$this->whereQuery->addWhereAny($filter);
		}

		return $this;
	}

	public function __toString() {
		if(!$this->whereQuery->getWhere()) {
			// We always need to have something so we don't end up with something like '... AND () AND ...'
			return '1=1';
		}

		$sql = DB::getConn()->sqlWhereToString(
			$this->whereQuery->getWhere(), 
			$this->whereQuery->getConnective()
		);
		
		$sql = preg_replace('[^\s*WHERE\s*]', '', $sql);

		return $sql;
	}
}
