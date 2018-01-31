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
	 *
	 * @param string|array $fieldExpression The predicate of the condition to remove
	 * (ignoring parameters). The expression will be considered a match if it's
	 * contained within any other predicate.
	 * @return DataQuery Self reference
	 */
	public function removeFilterOn($fieldExpression) {
		$matched = false;

		// If given a parameterised condition extract only the condition
		if(is_array($fieldExpression)) {
			reset($fieldExpression);
			$fieldExpression = key($fieldExpression);
		}

		$where = $this->query->toAppropriateExpression()->getWhere();
		// Iterate through each condition
		foreach($where as $i => $condition) {

			// Rewrite condition groups as plain conditions before comparison
			if($condition instanceof SQLConditionGroup) {
				$predicate = $condition->conditionSQL($parameters);
				$condition = array($predicate => $parameters);
			}

			// As each condition is a single length array, do a single
			// iteration to extract the predicate and parameters
			foreach($condition as $predicate => $parameters) {
				// @see SQLQuery::addWhere for why this is required here
				if(strpos($predicate, $fieldExpression) !== false) {
					unset($where[$i]);
					$matched = true;
				}
				// Enforce single-item condition predicate => parameters structure
				break;
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
	 * @param array|null $queriedColumns Any columns to filter the query by
	 * @return SQLQuery The finalised sql query
	 */
	public function getFinalisedQuery($queriedColumns = null) {
		if(!$queriedColumns) {
			$queriedColumns = $this->queriedColumns;
		}
		if($queriedColumns) {
			$queriedColumns = array_merge($queriedColumns, array('Created', 'LastEdited', 'ClassName'));
		}

		$query = clone $this->query;
		$ancestorTables = ClassInfo::ancestry($this->dataClass, true);

		// Generate the list of tables to iterate over and the list of columns required
		// by any existing where clauses. This second step is skipped if we're fetching
		// the whole dataobject as any required columns will get selected regardless.
		if($queriedColumns) {
			// Specifying certain columns allows joining of child tables
			$tableClasses = ClassInfo::dataClassesFor($this->dataClass);

			// Ensure that any filtered columns are included in the selected columns
			foreach ($query->getWhereParameterised($parameters) as $where) {
				// Check for any columns in the form '"Column" = ?' or '"Table"."Column"' = ?
				if(preg_match_all(
					'/(?:"(?<table>[^"]+)"\.)?"(?<column>[^"]+)"(?:[^\.]|$)/',
					$where, $matches, PREG_SET_ORDER
				)) {
					foreach($matches as $match) {
						$column = $match['column'];
						if (!in_array($column, $queriedColumns)) {
							$queriedColumns[] = $column;
						}
					}
				}
			}
		} else {
			$tableClasses = $ancestorTables;
		}

		$tableNames = array_values($tableClasses);
		$baseClass = $tableNames[0];

		// Iterate over the tables and check what we need to select from them. If any selects are made (or the table is
		// required for a select)
		foreach($tableClasses as $tableClass) {

			// Determine explicit columns to select
			$selectColumns = null;
			if ($queriedColumns) {
				// Restrict queried columns to that on the selected table
				$tableFields = DataObject::database_fields($tableClass, false);
				$selectColumns = array_intersect($queriedColumns, array_keys($tableFields));
			}

			// If this is a subclass without any explicitly requested columns, omit this from the query
			if(!in_array($tableClass, $ancestorTables) && empty($selectColumns)) continue;

			// Select necessary columns (unless an explicitly empty array)
			if($selectColumns !== array()) {
				$this->selectColumnsFromTable($query, $tableClass, $selectColumns);
			}

			// Join if not the base table
			if($tableClass !== $baseClass) {
				$query->addLeftJoin($tableClass, "\"$tableClass\".\"ID\" = \"$baseClass\".\"ID\"", $tableClass, 10);
			}
		}

		// Resolve colliding fields
		if($this->collidingFields) {
			foreach($this->collidingFields as $k => $collisions) {
				$caseClauses = array();
				foreach($collisions as $collision) {
					if(preg_match('/^"([^"]+)"/', $collision, $matches)) {
						$collisionBase = $matches[1];
						if(class_exists($collisionBase)) {
							$collisionClasses = ClassInfo::subclassesFor($collisionBase);
							$collisionClasses = Convert::raw2sql($collisionClasses, true);
							$caseClauses[] = "WHEN \"$baseClass\".\"ClassName\" IN ("
								. implode(", ", $collisionClasses) . ") THEN $collision";
						}
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
				$classNamesPlaceholders = DB::placeholders($classNames);
				$query->addWhere(array(
					"\"$baseClass\".\"ClassName\" IN ($classNamesPlaceholders)" => $classNames
				));
			}
		}

		$query->selectField("\"$baseClass\".\"ID\"", "ID");
		$query->selectField("
			CASE WHEN \"$baseClass\".\"ClassName\" IS NOT NULL THEN \"$baseClass\".\"ClassName\"
			ELSE ".Convert::raw2sql($baseClass, true)." END",
			"RecordClassName"
		);

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
			$i = 0;
			foreach($orderby as $k => $dir) {
				$newOrderby[$k] = $dir;

				// don't touch functions in the ORDER BY or public function calls
				// selected as fields
				if(strpos($k, '(') !== false) continue;

				$col = str_replace('"', '', trim($k));
				$parts = explode('.', $col);

				// Pull through SortColumn references from the originalSelect variables
				if(preg_match('/_SortColumn/', $col)) {
					if(isset($originalSelect[$col])) {
						$query->selectField($originalSelect[$col], $col);
					}

					continue;
				}

				if(count($parts) == 1) {

					if(DataObject::has_own_table_database_field($baseClass, $parts[0])) {
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

					if(!in_array($qualCol, $query->getSelect())) {
						unset($newOrderby[$k]);

						$newOrderby["\"_SortColumn$i\""] = $dir;
						$query->selectField($qualCol, "_SortColumn$i");

						$i++;
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
	 *
	 * @param array $parameters Out variable for parameters required for this query
	 * @return string The resulting SQL query (may be paramaterised)
	 */
	public function sql(&$parameters = array()) {
		return $this->getFinalisedQuery()->sql($parameters);
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
	 * @param String $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function max($field) {
		$table = ClassInfo::table_for_object_field($this->dataClass, $field);
		if (!$table || $table === 'DataObject') {
			return $this->aggregate("MAX(\"$field\")");
		}
		return $this->aggregate("MAX(\"$table\".\"$field\")");
	}

	/**
	 * Return the minimum value of the given field in this DataList
	 *
	 * @param String $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function min($field) {
		$table = ClassInfo::table_for_object_field($this->dataClass, $field);
		if (!$table || $table === 'DataObject') {
			return $this->aggregate("MIN(\"$field\")");
		}
		return $this->aggregate("MIN(\"$table\".\"$field\")");
	}

	/**
	 * Return the average value of the given field in this DataList
	 *
	 * @param String $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function avg($field) {
		$table = ClassInfo::table_for_object_field($this->dataClass, $field);
		if (!$table || $table === 'DataObject') {
			return $this->aggregate("AVG(\"$field\")");
		}
		return $this->aggregate("AVG(\"$table\".\"$field\")");
	}

	/**
	 * Return the sum of the values of the given field in this DataList
	 *
	 * @param String $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function sum($field) {
		$table = ClassInfo::table_for_object_field($this->dataClass, $field);
		if (!$table || $table === 'DataObject') {
			return $this->aggregate("SUM(\"$field\")");
		}
		return $this->aggregate("SUM(\"$table\".\"$field\")");
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
		$databaseFields = DataObject::database_fields($tableClass, false);
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
				$dbO->setTable($tableClass);
				$dbO->addToQuery($query);
			}
		}
	}

	/**
	 * Append a GROUP BY clause to this query.
	 *
	 * @param String $groupby Escaped SQL statement
	 */
	public function groupby($groupby) {
		$this->query->addGroupBy($groupby);
		return $this;
	}

	/**
	 * Append a HAVING clause to this query.
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
	 * Adds a WHERE clause.
	 *
	 * @see SQLQuery::addWhere() for syntax examples, although DataQuery
	 * won't expand multiple arguments as SQLQuery does.
	 *
	 * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
	 * paramaterised queries
	 * @return DataQuery
	 */
	public function where($filter) {
		if($filter) {
			$this->query->addWhere($filter);
		}
		return $this;
	}

	/**
	 * Append a WHERE with OR.
	 *
	 * @see SQLQuery::addWhere() for syntax examples, although DataQuery
	 * won't expand multiple method arguments as SQLQuery does.
	 *
	 * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
	 * paramaterised queries
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
	 * Set whether this query should be distinct or not.
	 *
	 * @param bool $value
	 * @return DataQuery
	 */
	public function distinct($value) {
		$this->query->setDistinct($value);
		return $this;
	}

	/**
	 * Add an INNER JOIN clause to this query.
	 *
	 * @param String $table The unquoted table name to join to.
	 * @param String $onClause The filter for the join (escaped SQL statement)
	 * @param String $alias An optional alias name (unquoted)
	 * @param int $order A numerical index to control the order that joins are added to the query; lower order values
	 * will cause the query to appear first. The default is 20, and joins created automatically by the
	 * ORM have a value of 10.
	 * @param array $parameters Any additional parameters if the join is a parameterised subquery
	 */
	public function innerJoin($table, $onClause, $alias = null, $order = 20, $parameters = array()) {
		if($table) {
			$this->query->addInnerJoin($table, $onClause, $alias, $order, $parameters);
		}
		return $this;
	}

	/**
	 * Add a LEFT JOIN clause to this query.
	 *
	 * @param string $table The unquoted table to join to.
	 * @param string $onClause The filter for the join (escaped SQL statement).
	 * @param string $alias An optional alias name (unquoted)
	 * @param int $order A numerical index to control the order that joins are added to the query; lower order values
	 * will cause the query to appear first. The default is 20, and joins created automatically by the
	 * ORM have a value of 10.
	 * @param array $parameters Any additional parameters if the join is a parameterised subquery
	 */
	public function leftJoin($table, $onClause, $alias = null, $order = 20, $parameters = array()) {
		if($table) {
			$this->query->addLeftJoin($table, $onClause, $alias, $order, $parameters);
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
			if ($component = $model->hasOneComponent($rel)) {
				if(!$this->query->isJoinedTo($component)) {
					$foreignKey = $rel;
					$realModelClass = ClassInfo::table_for_object_field($modelClass, "{$foreignKey}ID");
					$this->query->addLeftJoin($component,
						"\"$component\".\"ID\" = \"{$realModelClass}\".\"{$foreignKey}ID\"");

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

			} elseif ($component = $model->hasManyComponent($rel)) {
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

			} elseif ($component = $model->manyManyComponent($rel)) {
				list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
				$parentBaseClass = ClassInfo::baseDataClass($parentClass);
				$componentBaseClass = ClassInfo::baseDataClass($componentClass);
				$this->query->addLeftJoin($relationTable,
					"\"$relationTable\".\"$parentField\" = \"$parentBaseClass\".\"ID\"");
				if (!$this->query->isJoinedTo($componentBaseClass)) {
				$this->query->addLeftJoin($componentBaseClass,
					"\"$relationTable\".\"$componentField\" = \"$componentBaseClass\".\"ID\"");
				}
				if(ClassInfo::hasTable($componentClass)	&& !$this->query->isJoinedTo($componentClass)) {
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
		$subSelectSQL = $subSelect->sql($subSelectParameters);
		$this->where(array($this->expressionForField($field)." NOT IN ($subSelectSQL)" => $subSelectParameters));

		return $this;
	}

	/**
	 * Select only the given fields from the given table.
	 *
	 * @param String $table Unquoted table name (will be escaped automatically)
	 * @param Array $fields Database column names (will be escaped automatically)
	 */
	public function selectFromTable($table, $fields) {
		$fieldExpressions = array_map(function($item) use($table) {
			return Convert::symbol2sql("{$table}.{$item}");
		}, $fields);

		$this->query->setSelect($fieldExpressions);

		return $this;
	}

	/**
	 * Add the given fields from the given table to the select statement.
	 *
	 * @param String $table Unquoted table name (will be escaped automatically)
	 * @param Array $fields Database column names (will be escaped automatically)
	 */
	public function addSelectFromTable($table, $fields) {
		$fieldExpressions = array_map(function($item) use($table) {
			return Convert::symbol2sql("{$table}.{$item}");
		}, $fields);

		$this->query->addSelect($fieldExpressions);

		return $this;
	}

	/**
	 * Query the given field column from the database and return as an array.
	 *
	 * @param string $field See {@link expressionForField()}.
	 * @return array List of column values for the specified column
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
class DataQuery_SubGroup extends DataQuery implements SQLConditionGroup {

	/**
	 *
	 * @var SQLQuery
	 */
	protected $whereQuery;

	public function __construct(DataQuery $base, $connective) {
		$this->dataClass = $base->dataClass;
		$this->query = $base->query;
		$this->whereQuery = new SQLQuery();
		$this->whereQuery->setConnective($connective);

		$base->where($this);
	}

	public function where($filter) {
		if($filter) {
			$this->whereQuery->addWhere($filter);
		}

		return $this;
	}

	public function whereAny($filter) {
		if($filter) {
			$this->whereQuery->addWhereAny($filter);
		}

		return $this;
	}

	public function conditionSQL(&$parameters) {
		$parameters = array();

		// Ignore empty conditions
		$query = $this->whereQuery->toAppropriateExpression();
		$where = $query->getWhere();
		if(empty($where)) {
			return null;
		}

		// Allow database to manage joining of conditions
		$sql = DB::get_conn()->getQueryBuilder()->buildWhereFragment($query, $parameters);
		return preg_replace('/^\s*WHERE\s*/i', '', $sql);
	}
}
