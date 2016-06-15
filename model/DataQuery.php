<?php

/**
 * An object representing a query of data from the DataObject's supporting database.
 * Acts as a wrapper over {@link SQLSelect} and performs all of the query generation.
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
	 * @var SQLSelect
	 */
	protected $query;

	/**
	 * Map of all field names to an array of conflicting column SQL
	 *
	 * E.g.
	 * array(
	 *   'Title' => array(
	 *     '"MyTable"."Title"',
	 *     '"AnotherTable"."Title"',
	 *   )
	 * )
	 *
	 * @var array
	 */
	protected $collidingFields = array();

	private $queriedColumns = null;

	/**
	 * @var bool
	 */
	private $queryFinalised = false;

	// TODO: replace subclass_access with this
	protected $querySubclasses = true;
	// TODO: replace restrictclasses with this
	protected $filterByClassName = true;

	/**
	 * Create a new DataQuery.
	 *
	 * @param string $dataClass The name of the DataObject class that you wish to query
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
	 *
	 * @return string
	 */
	public function dataClass() {
		return $this->dataClass;
	}

	/**
	 * Return the {@link SQLSelect} object that represents the current query; note that it will
	 * be a clone of the object.
	 *
	 * @return SQLSelect
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

		$where = $this->query->getWhere();
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
				// @see SQLSelect::addWhere for why this is required here
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
	protected function initialiseQuery() {
		// Join on base table and let lazy loading join subtables
		$baseClass = DataObject::getSchema()->baseDataClass($this->dataClass());
		if(!$baseClass) {
			throw new InvalidArgumentException("DataQuery::create() Can't find data classes for '{$this->dataClass}'");
		}

		// Build our intial query
		$this->query = new SQLSelect(array());
		$this->query->setDistinct(true);

		if($sort = singleton($this->dataClass)->stat('default_sort')) {
			$this->sort($sort);
		}

		$baseTable = DataObject::getSchema()->tableName($baseClass);
		$this->query->setFrom("\"{$baseTable}\"");

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
	 * @return SQLSelect The finalised sql query
	 */
	public function getFinalisedQuery($queriedColumns = null) {
		if(!$queriedColumns) {
			$queriedColumns = $this->queriedColumns;
		}
		if($queriedColumns) {
			$queriedColumns = array_merge($queriedColumns, array('Created', 'LastEdited', 'ClassName'));
		}

		$schema = DataObject::getSchema();
		$query = clone $this->query;
		$baseDataClass = $schema->baseDataClass($this->dataClass());
		$baseIDColumn = $schema->sqlColumnForField($baseDataClass, 'ID');
		$ancestorClasses = ClassInfo::ancestry($this->dataClass(), true);

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
			$tableClasses = $ancestorClasses;
		}

		// Iterate over the tables and check what we need to select from them. If any selects are made (or the table is
		// required for a select)
		foreach($tableClasses as $tableClass) {

			// Determine explicit columns to select
			$selectColumns = null;
			if ($queriedColumns) {
				// Restrict queried columns to that on the selected table
				$tableFields = DataObject::database_fields($tableClass);
				unset($tableFields['ID']);
				$selectColumns = array_intersect($queriedColumns, array_keys($tableFields));
			}

			// If this is a subclass without any explicitly requested columns, omit this from the query
			if(!in_array($tableClass, $ancestorClasses) && empty($selectColumns)) {
				continue;
			}

			// Select necessary columns (unless an explicitly empty array)
			if($selectColumns !== array()) {
				$this->selectColumnsFromTable($query, $tableClass, $selectColumns);
			}

			// Join if not the base table
			if($tableClass !== $baseDataClass) {
				$tableName = $schema->tableName($tableClass);
				$query->addLeftJoin(
					$tableName,
					"\"{$tableName}\".\"ID\" = {$baseIDColumn}",
					$tableName,
					10
				);
			}
		}

		// Resolve colliding fields
		if($this->collidingFields) {
			foreach($this->collidingFields as $collisionField => $collisions) {
				$caseClauses = array();
				foreach($collisions as $collision) {
					if(preg_match('/^"(?<table>[^"]+)"\./', $collision, $matches)) {
						$collisionTable = $matches['table'];
						$collisionClass = $schema->tableClass($collisionTable);
						if($collisionClass) {
							$collisionClassColumn = $schema->sqlColumnForField($collisionClass, 'ClassName');
							$collisionClasses = ClassInfo::subclassesFor($collisionClass);
							$collisionClassesSQL = implode(', ', Convert::raw2sql($collisionClasses, true));
							$caseClauses[] = "WHEN {$collisionClassColumn} IN ({$collisionClassesSQL}) THEN $collision";
						}
					} else {
						user_error("Bad collision item '$collision'", E_USER_WARNING);
					}
				}
				$query->selectField("CASE " . implode( " ", $caseClauses) . " ELSE NULL END", $collisionField);
			}
		}


		if($this->filterByClassName) {
			// If querying the base class, don't bother filtering on class name
			if($this->dataClass != $baseDataClass) {
				// Get the ClassName values to filter to
				$classNames = ClassInfo::subclassesFor($this->dataClass);
				$classNamesPlaceholders = DB::placeholders($classNames);
				$baseClassColumn = $schema->sqlColumnForField($baseDataClass, 'ClassName');
				$query->addWhere(array(
					"{$baseClassColumn} IN ($classNamesPlaceholders)" => $classNames
				));
			}
		}

		// Select ID
		$query->selectField($baseIDColumn, "ID");

		// Select RecordClassName
		$baseClassColumn = $schema->sqlColumnForField($baseDataClass, 'ClassName');
		$query->selectField("
			CASE WHEN {$baseClassColumn} IS NOT NULL THEN {$baseClassColumn}
			ELSE ".Convert::raw2sql($baseDataClass, true)." END",
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
	 * @param SQLSelect $query
	 * @param array $originalSelect
	 * @return null
	 */
	protected function ensureSelectContainsOrderbyColumns($query, $originalSelect = array()) {
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
					// Get expression for sort value
					$qualCol = "\"{$parts[0]}\"";
					$table = DataObject::getSchema()->tableForField($this->dataClass(), $parts[0]);
					if($table) {
						$qualCol = "\"{$table}\".{$qualCol}";
					}

					// remove original sort
					unset($newOrderby[$k]);

					// add new columns sort
					$newOrderby[$qualCol] = $dir;

					// To-do: Remove this if block once SQLSelect::$select has been refactored to store getSelect()
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
	 * Execute the query and return the result as {@link SS_Query} object.
	 *
	 * @return SS_Query
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
	 *
	 * @return int
	 */
	public function count() {
		$quotedColumn = DataObject::getSchema()->sqlColumnForField($this->dataClass(), 'ID');
		return $this->getFinalisedQuery()->count("DISTINCT {$quotedColumn}");
	}

	/**
	 * Return the maximum value of the given field in this DataList
	 *
	 * @param String $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function max($field) {
		return $this->aggregate("MAX(\"$field\")");
	}

	/**
	 * Return the minimum value of the given field in this DataList
	 *
	 * @param string $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function min($field) {
		return $this->aggregate("MIN(\"$field\")");
	}

	/**
	 * Return the average value of the given field in this DataList
	 *
	 * @param string $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function avg($field) {
		return $this->aggregate("AVG(\"$field\")");
	}

	/**
	 * Return the sum of the values of the given field in this DataList
	 *
	 * @param string $field Unquoted database column name. Will be ANSI quoted
	 * automatically so must not contain double quotes.
	 * @return string
	 */
	public function sum($field) {
		return $this->aggregate("SUM(\"$field\")");
	}

	/**
	 * Runs a raw aggregate expression.  Please handle escaping yourself
	 *
	 * @param string $expression An aggregate expression, such as 'MAX("Balance")', or a set of them
	 * (as an escaped SQL statement)
	 * @return string
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
	 *
	 * @param SQLSelect $query
	 * @param string $tableClass Class to select from
	 * @param array $columns
	 */
	protected function selectColumnsFromTable(SQLSelect &$query, $tableClass, $columns = null) {
		// Add SQL for multi-value fields
		$databaseFields = DataObject::database_fields($tableClass);
		$compositeFields = DataObject::composite_fields($tableClass, false);
		unset($databaseFields['ID']);
		foreach($databaseFields as $k => $v) {
			if((is_null($columns) || in_array($k, $columns)) && !isset($compositeFields[$k])) {
				// Update $collidingFields if necessary
				$expressionForField = $query->expressionForField($k);
				$quotedField = DataObject::getSchema()->sqlColumnForField($tableClass, $k);
				if($expressionForField) {
					if(!isset($this->collidingFields[$k])) {
						$this->collidingFields[$k] = array($expressionForField);
					}
					$this->collidingFields[$k][] = $quotedField;
				} else {
					$query->selectField($quotedField, $k);
				}
			}
		}
		foreach($compositeFields as $k => $v) {
			if((is_null($columns) || in_array($k, $columns)) && $v) {
				$tableName = DataObject::getSchema()->tableName($tableClass);
				$dbO = Object::create_from_string($v, $k);
				$dbO->setTable($tableName);
				$dbO->addToQuery($query);
			}
		}
	}

	/**
	 * Append a GROUP BY clause to this query.
	 *
	 * @param string $groupby Escaped SQL statement
	 * @return $this
	 */
	public function groupby($groupby) {
		$this->query->addGroupBy($groupby);
		return $this;
	}

	/**
	 * Append a HAVING clause to this query.
	 *
	 * @param string $having Escaped SQL statement
	 * @return $this
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
	 * @see SQLSelect::addWhere() for syntax examples, although DataQuery
	 * won't expand multiple arguments as SQLSelect does.
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
	 * @see SQLSelect::addWhere() for syntax examples, although DataQuery
	 * won't expand multiple method arguments as SQLSelect does.
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
	 * @see SQLSelect::orderby()
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
	 * @return $this
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
	 * @return $this
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
	 * @return $this
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
	 * @param string|array $relation The array/dot-syntax relation to follow
	 * @param bool $linearOnly Set to true to restrict to linear relations only. Set this
	 * if this relation will be used for sorting, and should not include duplicate rows.
	 * @return string The model class of the related item
	 */
	public function applyRelation($relation, $linearOnly = false) {
		// NO-OP
		if(!$relation) {
			return $this->dataClass;
		}

		if(is_string($relation)) {
			$relation = explode(".", $relation);
		}

		$modelClass = $this->dataClass;

		foreach($relation as $rel) {
			$model = singleton($modelClass);
			if ($component = $model->hasOneComponent($rel)) {
				// Join via has_one
				$this->joinHasOneRelation($modelClass, $rel, $component);
				$modelClass = $component;

			} elseif ($component = $model->hasManyComponent($rel)) {
				// Fail on non-linear relations
				if($linearOnly) {
					throw new InvalidArgumentException("$rel is not a linear relation on model $modelClass");
				}
				// Join via has_many
				$this->joinHasManyRelation($modelClass, $rel, $component);
				$modelClass = $component;

			} elseif ($component = $model->manyManyComponent($rel)) {
				// Fail on non-linear relations
				if($linearOnly) {
					throw new InvalidArgumentException("$rel is not a linear relation on model $modelClass");
				}
				// Join via many_many
				list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
				$this->joinManyManyRelationship(
					$parentClass, $componentClass, $parentField, $componentField, $relationTable
				);
				$modelClass = $componentClass;

			} else {
				throw new InvalidArgumentException("$rel is not a relation on model $modelClass");
			}
		}

		return $modelClass;
	}

	/**
	 * Join the given class to this query with the given key
	 *
	 * @param string $localClass Name of class that has the has_one to the joined class
	 * @param string $localField Name of the has_one relationship to joi
	 * @param string $foreignClass Class to join
	 */
	protected function joinHasOneRelation($localClass, $localField, $foreignClass)
	{
		if (!$foreignClass) {
			throw new InvalidArgumentException("Could not find a has_one relationship {$localField} on {$localClass}");
		}

		if ($foreignClass === 'DataObject') {
			throw new InvalidArgumentException(
				"Could not join polymorphic has_one relationship {$localField} on {$localClass}"
			);
		}
		$schema = DataObject::getSchema();

		// Skip if already joined
		$foreignBaseClass = $schema->baseDataClass($foreignClass);
		$foreignBaseTable = $schema->tableName($foreignBaseClass);
		if($this->query->isJoinedTo($foreignBaseTable)) {
			return;
		}

		// Join base table
		$foreignIDColumn = $schema->sqlColumnForField($foreignBaseClass, 'ID');
		$localColumn = $schema->sqlColumnForField($localClass, "{$localField}ID");
		$this->query->addLeftJoin($foreignBaseTable, "{$foreignIDColumn} = {$localColumn}");

		/**
		 * add join clause to the component's ancestry classes so that the search filter could search on
		 * its ancestor fields.
		 */
		$ancestry = ClassInfo::ancestry($foreignClass, true);
		if(!empty($ancestry)){
			$ancestry = array_reverse($ancestry);
			foreach($ancestry as $ancestor){
				$ancestorTable = $schema->tableName($ancestor);
				if($ancestorTable !== $foreignBaseTable) {
					$this->query->addLeftJoin($ancestorTable, "{$foreignIDColumn} = \"{$ancestorTable}\".\"ID\"");
				}
			}
		}
	}

	/**
	 * Join the given has_many relation to this query.
	 *
	 * Doesn't work with polymorphic relationships
	 *
	 * @param string $localClass Name of class that has the has_many to the joined class
	 * @param string $localField Name of the has_many relationship to join
	 * @param string $foreignClass Class to join
	 */
	protected function joinHasManyRelation($localClass, $localField, $foreignClass) {
		if(!$foreignClass || $foreignClass === 'DataObject') {
			throw new InvalidArgumentException("Could not find a has_many relationship {$localField} on {$localClass}");
		}
		$schema = DataObject::getSchema();

		// Skip if already joined
		$foreignTable = $schema->tableName($foreignClass);
		if($this->query->isJoinedTo($foreignTable)) {
			return;
		}

		// Join table with associated has_one
		/** @var DataObject $model */
		$model = singleton($localClass);
		$foreignKey = $model->getRemoteJoinField($localField, 'has_many', $polymorphic);
		$localIDColumn = $schema->sqlColumnForField($localClass, 'ID');
		if($polymorphic) {
			$foreignKeyIDColumn = $schema->sqlColumnForField($foreignClass, "{$foreignKey}ID");
			$foreignKeyClassColumn = $schema->sqlColumnForField($foreignClass, "{$foreignKey}Class");
			$localClassColumn = $schema->sqlColumnForField($localClass, 'ClassName');
			$this->query->addLeftJoin(
				$foreignTable,
				"{$foreignKeyIDColumn} = {$localIDColumn} AND {$foreignKeyClassColumn} = {$localClassColumn}"
			);
		} else {
			$foreignKeyIDColumn = $schema->sqlColumnForField($foreignClass, $foreignKey);
			$this->query->addLeftJoin($foreignTable, "{$foreignKeyIDColumn} = {$localIDColumn}");
		}

		/**
		 * add join clause to the component's ancestry classes so that the search filter could search on
		 * its ancestor fields.
		 */
		$ancestry = ClassInfo::ancestry($foreignClass, true);
		$ancestry = array_reverse($ancestry);
		foreach($ancestry as $ancestor) {
			$ancestorTable = $schema->tableName($ancestor);
			if($ancestorTable !== $foreignTable) {
				$this->query->addInnerJoin($ancestorTable, "\"{$foreignTable}\".\"ID\" = \"{$ancestorTable}\".\"ID\"");
			}
		}
	}

	/**
	 * Join table via many_many relationship
	 *
	 * @param string $parentClass
	 * @param string $componentClass
	 * @param string $parentField
	 * @param string $componentField
	 * @param string $relationTable Name of relation table
	 */
	protected function joinManyManyRelationship($parentClass, $componentClass, $parentField, $componentField, $relationTable) {
		$schema = DataObject::getSchema();

		// Join on parent table
		$parentIDColumn = $schema->sqlColumnForField($parentClass, 'ID');
		$this->query->addLeftJoin(
			$relationTable,
			"\"$relationTable\".\"$parentField\" = {$parentIDColumn}"
		);

		// Join on base table of component class
		$componentBaseClass = $schema->baseDataClass($componentClass);
		$componentBaseTable = $schema->tableName($componentBaseClass);
		$componentIDColumn = $schema->sqlColumnForField($componentBaseClass, 'ID');
		if (!$this->query->isJoinedTo($componentBaseTable)) {
			$this->query->addLeftJoin(
				$componentBaseTable,
				"\"$relationTable\".\"$componentField\" = {$componentIDColumn}"
			);
		}

		/**
		 * add join clause to the component's ancestry classes so that the search filter could search on
		 * its ancestor fields.
		 */
		$ancestry = ClassInfo::ancestry($componentClass, true);
		$ancestry = array_reverse($ancestry);
		foreach($ancestry as $ancestor) {
			$ancestorTable = $schema->tableName($ancestor);
			if($ancestorTable != $componentBaseTable && !$this->query->isJoinedTo($ancestorTable)) {
				$this->query->addLeftJoin($ancestorTable, "{$componentIDColumn} = \"{$ancestorTable}\".\"ID\"");
			}
		}
	}

	/**
	 * Removes the result of query from this query.
	 *
	 * @param DataQuery $subtractQuery
	 * @param string $field
	 * @return $this
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
	 * Select the given fields from the given table.
	 *
	 * @param string $table Unquoted table name (will be escaped automatically)
	 * @param array $fields Database column names (will be escaped automatically)
	 * @return $this
	 */
	public function selectFromTable($table, $fields) {
		$fieldExpressions = array_map(function($item) use($table) {
			return "\"{$table}\".\"{$item}\"";
		}, $fields);

		$this->query->setSelect($fieldExpressions);

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
	 * the full composite SQL statement, or the alias set through {@link SQLSelect->selectField()}.
	 * @return String The expression used to query this field via this DataQuery
	 */
	protected function expressionForField($field) {
		// Prepare query object for selecting this field
		$query = $this->getFinalisedQuery(array($field));

		// Allow query to define the expression for this field
		$expression = $query->expressionForField($field);
		if(!empty($expression)) {
			return $expression;
		}

		// Special case for ID, if not provided
		if($field === 'ID') {
			return DataObject::getSchema()->sqlColumnForField($this->dataClass, 'ID');
		}
		return null;
	}

	/**
	 * Select the given field expressions.
	 *
	 * @param $fieldExpression String The field to select (escaped SQL statement)
	 * @param $alias String The alias of that field (escaped SQL statement)
	 */
	public function selectField($fieldExpression, $alias = null) {
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
	 *
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function setQueryParam($key, $value) {
		$this->queryParams[$key] = $value;
		return $this;
	}

	/**
	 * Set an arbitrary query parameter, that can be used by decorators to add additional meta-data to the query.
	 *
	 * @param string $key
	 * @return string
	 */
	public function getQueryParam($key) {
		if(isset($this->queryParams[$key])) {
			return $this->queryParams[$key];
		}
		return null;
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
 * Stores the clauses for the subgroup inside a specific {@link SQLSelect} object.
 * All non-where methods call their DataQuery versions, which uses the base
 * query object.
 *
 * @package framework
 */
class DataQuery_SubGroup extends DataQuery implements SQLConditionGroup {

	/**
	 *
	 * @var SQLSelect
	 */
	protected $whereQuery;

	public function __construct(DataQuery $base, $connective) {
		parent::__construct($base->dataClass);
		$this->query = $base->query;
		$this->whereQuery = new SQLSelect();
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
		$where = $this->whereQuery->getWhere();
		if(empty($where)) {
			return null;
		}

		// Allow database to manage joining of conditions
		$sql = DB::get_conn()->getQueryBuilder()->buildWhereFragment($this->whereQuery, $parameters);
		return preg_replace('/^\s*WHERE\s*/i', '', $sql);
	}
}
