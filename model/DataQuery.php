<?php

/**
 * An object representing a query of data from the DataObject's supporting database.
 * Acts as a wrapper over {@link SQLQuery} and performs all of the query generation.
 * Used extensively by {@link DataList}.
 *
 * @subpackage model
 * @package sapphire
 */
class DataQuery {
	
	/**
	 * @var String
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
	function __construct($dataClass) {
		$this->dataClass = $dataClass;
		$this->initialiseQuery();
	}
	
	/**
	 * Clone this object
	 */
	function __clone() {
		$this->query = clone $this->query;
	}
	
	/**
	 * Return the {@link DataObject} class that is being queried.
	 */
	function dataClass() {
	    return $this->dataClass;
	}

	/**
	 * Return the {@link SQLQuery} object that represents the current query; note that it will
	 * be a clone of the object.
	 */
	function query() {
		return $this->getFinalisedQuery();
	}
	
	
	/**
	 * Remove a filter from the query
	 */
	function removeFilterOn($fieldExpression) {
		$matched = false;
		foreach($this->query->where as $i=>$item) {
			if(strpos($item, $fieldExpression) !== false) {
				unset($this->query->where[$i]);
				$matched = true;
			}
		}
		
		if(!$matched) throw new InvalidArgumentException("Couldn't find $fieldExpression in the query filter.");
		
		return $this;
	}
	
	/**
	 * Set up the simplest intial query
	 */
	function initialiseQuery() {
		// Get the tables to join to
		$tableClasses = ClassInfo::dataClassesFor($this->dataClass);
		
		// Error checking
		if(!$tableClasses) {
			if(!SS_ClassLoader::instance()->hasManifest()) {
				user_error("DataObjects have been requested before the manifest is loaded. Please ensure you are not querying the database in _config.php.", E_USER_ERROR);
			} else {
				user_error("DataObject::buildSQL: Can't find data classes (classes linked to tables) for $this->dataClass. Please ensure you run dev/build after creating a new DataObject.", E_USER_ERROR);
			}
		}

		$baseClass = array_shift($tableClasses);
		$select = array("\"$baseClass\".*");

		// Build our intial query
		$this->query = new SQLQuery(array());
		$this->query->distinct = true;
		
		if($sort = singleton($this->dataClass)->stat('default_sort')) {
			$this->sort($sort);
		}

		$this->query->from("\"$baseClass\"");
		$this->selectAllFromTable($this->query, $baseClass);

		singleton($this->dataClass)->extend('augmentDataQueryCreation', $this->query, $this);
	}

	/**
	 * Ensure that the query is ready to execute.
	 */
	function getFinalisedQuery() {
		$query = clone $this->query;
		
		// Get the tables to join to
		$tableClasses = ClassInfo::dataClassesFor($this->dataClass);
		$baseClass = array_shift($tableClasses);
		
		$collidingFields = array();

		// Join all the tables
		if($this->querySubclasses) {
			foreach($tableClasses as $tableClass) {
				$query->leftJoin($tableClass, "\"$tableClass\".\"ID\" = \"$baseClass\".\"ID\"") ;
				$this->selectAllFromTable($query, $tableClass);
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
						$caseClauses[] = "WHEN \"$baseClass\".\"ClassName\" IN ('"
							. implode("', '", $collisionClasses) . "') THEN $collision";
					} else {
						user_error("Bad collision item '$collision'", E_USER_WARNING);
					}
				}
				$query->select[$k] = "CASE " . implode( " ", $caseClauses) . " ELSE NULL END"
					.  " AS \"$k\"";
			}
		}


		if($this->filterByClassName) {
			// If querying the base class, don't bother filtering on class name
			if($this->dataClass != $baseClass) {
				// Get the ClassName values to filter to
				$classNames = ClassInfo::subclassesFor($this->dataClass);
				if(!$classNames) user_error("DataList::create() Can't find data sub-classes for '$callerClass'");
				$query->where[] = "\"$baseClass\".\"ClassName\" IN ('" . implode("','", $classNames) . "')";
			}
		}

		$query->select[] = "\"$baseClass\".\"ID\"";
		$query->select[] = "CASE WHEN \"$baseClass\".\"ClassName\" IS NOT NULL THEN \"$baseClass\".\"ClassName\" ELSE '$baseClass' END AS \"RecordClassName\"";

		// TODO: Versioned, Translatable, SiteTreeSubsites, etc, could probably be better implemented as subclasses of DataQuery
		singleton($this->dataClass)->extend('augmentSQL', $query, $this);

		$this->ensureSelectContainsOrderbyColumns($query);

		return $query;
	}

	/**
	 * Ensure that if a query has an order by clause, those columns are present in the select.
	 * 
	 * @param SQLQuery $query
	 * @return null
	 */
	protected function ensureSelectContainsOrderbyColumns($query) {
		$tableClasses = ClassInfo::dataClassesFor($this->dataClass);
		$baseClass = array_shift($tableClasses);

		if($query->orderby) {
			$orderby = $query->getOrderBy();

			foreach($orderby as $k => $dir) {
				// don't touch functions in the ORDER BY or function calls 
				// selected as fields
				if(strpos($k, '(') !== false || preg_match('/_SortColumn/', $k)) 
					continue;
				
				$col = str_replace('"', '', trim($k));
				$parts = explode('.', $col);

				if(count($parts) == 1) {
					$databaseFields = DataObject::database_fields($baseClass);
	
					// database_fields() doesn't return ID, so we need to 
					// manually add it here
					$databaseFields['ID'] = true;
				
					if(isset($databaseFields[$parts[0]])) {
						$qualCol = "\"$baseClass\".\"{$parts[0]}\"";
						
						// remove original sort
						unset($orderby[$k]);
						
						// add new columns sort
						$orderby[$qualCol] = $dir;
							
					} else {
						$qualCol = "\"$parts[0]\"";
					}
					
					if(!isset($query->select[$col]) && !in_array($qualCol, $query->select)) {
						$query->select[] = $qualCol;
					}
				} else {
					$qualCol = '"' . implode('"."', $parts) . '"';
					
					if(!in_array($qualCol, $query->select)) {
						$query->select[] = $qualCol;
					}
				}
			}

			$query->orderby = $orderby;
		}
	}

	/**
	 * Execute the query and return the result as {@link Query} object.
	 */
	function execute() {
		return $this->getFinalisedQuery()->execute();
	}

	/**
	 * Return this query's SQL
	 */
	function sql() {
		return $this->getFinalisedQuery()->sql();
	}

	/**
	 * Return the number of records in this query.
	 * Note that this will issue a separate SELECT COUNT() query.
	 */
	function count() {
	    $baseClass = ClassInfo::baseDataClass($this->dataClass);
		return $this->getFinalisedQuery()->count("DISTINCT \"$baseClass\".\"ID\"");
	}

	/**
	 * Return the maximum value of the given field in this DataList
	 */
	function Max($field) {
	    return $this->getFinalisedQuery()->aggregate("MAX(\"$field\")")->execute()->value();
	}

	/**
	 * Return the minimum value of the given field in this DataList
	 */
	function Min($field) {
	    return $this->getFinalisedQuery()->aggregate("MIN(\"$field\")")->execute()->value();
	}
	
	/**
	 * Return the average value of the given field in this DataList
	 */
	function Avg($field) {
	    return $this->getFinalisedQuery()->aggregate("AVG(\"$field\")")->execute()->value();
	}

	/**
	 * Return the sum of the values of the given field in this DataList
	 */
	function Sum($field) {
	    return $this->getFinalisedQuery()->aggregate("SUM(\"$field\")")->execute()->value();
	}

	/**
	 * Return the first row that would be returned by this full DataQuery
	 * Note that this will issue a separate SELECT ... LIMIT 1 query.
	 */
	function firstRow() {
		return $this->getFinalisedQuery()->firstRow();
	}

	/**
	 * Return the last row that would be returned by this full DataQuery
	 * Note that this will issue a separate SELECT ... LIMIT query.
	 */
	function lastRow() {
		return $this->getFinalisedQuery()->lastRow();
	}

	/**
	 * Update the SELECT clause of the query with the columns from the given table
	 */
	protected function selectAllFromTable(SQLQuery &$query, $tableClass) {
    	// Add SQL for multi-value fields
    	$databaseFields = DataObject::database_fields($tableClass);
    	$compositeFields = DataObject::composite_fields($tableClass, false);
    	if($databaseFields) foreach($databaseFields as $k => $v) {
    		if(!isset($compositeFields[$k])) {
    			// Update $collidingFields if necessary
    			if(isset($query->select[$k])) {
    				if(!isset($this->collidingFields[$k])) $this->collidingFields[$k] = array($query->select[$k]);
    				$this->collidingFields[$k][] = "\"$tableClass\".\"$k\"";
				
    			} else {
    				$query->select[$k] = "\"$tableClass\".\"$k\"";
    			}
    		}
    	}
    	if($compositeFields) foreach($compositeFields as $k => $v) {
			if($v) {
			    $dbO = Object::create_from_string($v, $k);
    		    $dbO->addToQuery($query);
		    }
    	}
	}
	
	/**
	 * Set the HAVING clause of this query
	 */
	function having($having) {
		if($having) {
			$clone = $this;
			$clone->query->having[] = $having;
			return $clone;
		} else {
			return $this;
		}
	}

	/**
	 * Set the WHERE clause of this query
	 */
	function where($filter) {
		if($filter) {
			$clone = $this;
			$clone->query->where($filter);
			return $clone;
		} else {
			return $this;
		}
	}

	/**
	 * Set a WHERE with OR
	 *
	 * @param array $filter
	 * @return DataQuery
	 * @example $dataQuery->whereAny(array("Monkey = 'Chimp'", "Color = 'Brown'"));
	 */
	function whereAny($filter) {
		if($filter) {
			$clone = $this;
			$clone->query->whereAny($filter);
			return $clone;
		} else {
			return $this;
		}
	}
	
	/**
	 * Set the ORDER BY clause of this query
	 *
	 * @see SQLQuery::orderby()
	 *
	 * @return DataQuery
	 */
	function sort($sort = null, $direction = null, $clear = true) {
		$clone = $this;
		$clone->query->orderby($sort, $direction, $clear);
			
		return $clone;
	}
	
	/**
	 * Reverse order by clause
	 *
	 * @return DataQuery
	 */
	function reverseSort() {
		$clone = $this;
		
		$clone->query->reverseOrderBy();
		return $clone;
	}
	
	/**
	 * Set the limit of this query
	 */
	function limit($limit, $offset = 0) {
		$clone = $this;
		$clone->query->limit($limit, $offset);
		return $clone;
	}

	/**
	 * Add a join clause to this query
	 * @deprecated 3.0 Use innerJoin() or leftJoin() instead.
	 */
	function join($join) {
		Deprecation::notice('3.0', 'Use innerJoin() or leftJoin() instead.');
		if($join) {
			$clone = $this;
			$clone->query->from[] = $join;
			// TODO: This needs to be resolved for all databases
			if(DB::getConn() instanceof MySQLDatabase) $clone->query->groupby[] = reset($clone->query->from) . ".\"ID\"";
			return $clone;
		} else {
			return $this;
		}
	}
	
	/**
	 * Add an INNER JOIN clause to this queyr
	 * @param $table The table to join to.
	 * @param $onClause The filter for the join.
	 */
	public function innerJoin($table, $onClause, $alias = null) {
		if($table) {
			$clone = $this;
			$clone->query->innerJoin($table, $onClause, $alias);
			return $clone;
		} else {
			return $this;
		}
	}

	/**
	 * Add a LEFT JOIN clause to this queyr
	 * @param $table The table to join to.
	 * @param $onClause The filter for the join.
	 */
	public function leftJoin($table, $onClause, $alias = null) {
		if($table) {
			$clone = $this;
			$clone->query->leftJoin($table, $onClause, $alias);
			return $clone;
		} else {
			return $this;
		}
	}

	/**
	 * Traverse the relationship fields, and add the table
	 * mappings to the query object state. This has to be called
	 * in any overloaded {@link SearchFilter->apply()} methods manually.
	 * 
	 * @param $relation The array/dot-syntax relation to follow
	 * @return The model class of the related item
	 */
	function applyRelation($relation) {
	    // NO-OP
	    if(!$relation) return $this->dataClass;
	    
	    if(is_string($relation)) $relation = explode(".", $relation);
	    
	    $modelClass = $this->dataClass;
	    
    	foreach($relation as $rel) {
    		$model = singleton($modelClass);
    		if ($component = $model->has_one($rel)) {	
    			if(!$this->query->isJoinedTo($component)) {
    				$foreignKey = $model->getReverseAssociation($component);
    				$this->query->leftJoin($component, "\"$component\".\"ID\" = \"{$modelClass}\".\"{$foreignKey}ID\"");
				
    				/**
    				 * add join clause to the component's ancestry classes so that the search filter could search on its 
    				 * ancester fields.
    				 */
    				$ancestry = ClassInfo::ancestry($component, true);
    				if(!empty($ancestry)){
    					$ancestry = array_reverse($ancestry);
    					foreach($ancestry as $ancestor){
    						if($ancestor != $component){
    							$this->query->innerJoin($ancestor, "\"$component\".\"ID\" = \"$ancestor\".\"ID\"");
    							$component=$ancestor;
    						}
    					}
    				}
    			}
    			$modelClass = $component;

    		} elseif ($component = $model->has_many($rel)) {
    			if(!$this->query->isJoinedTo($component)) {
    			 	$ancestry = $model->getClassAncestry();
    				$foreignKey = $model->getRemoteJoinField($rel);
    				$this->query->leftJoin($component, "\"$component\".\"{$foreignKey}\" = \"{$ancestry[0]}\".\"ID\"");
    				/**
    				 * add join clause to the component's ancestry classes so that the search filter could search on its 
    				 * ancestor fields.
    				 */
    				$ancestry = ClassInfo::ancestry($component, true);
    				if(!empty($ancestry)){
    					$ancestry = array_reverse($ancestry);
    					foreach($ancestry as $ancestor){
    						if($ancestor != $component){
    							$this->query->innerJoin($ancestor, "\"$component\".\"ID\" = \"$ancestor\".\"ID\"");
    							$component=$ancestor;
    						}
    					}
    				}
    			}
    			$modelClass = $component;

    		} elseif ($component = $model->many_many($rel)) {
    			list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
    			$parentBaseClass = ClassInfo::baseDataClass($parentClass);
    			$componentBaseClass = ClassInfo::baseDataClass($componentClass);
    			$this->query->innerJoin($relationTable, "\"$relationTable\".\"$parentField\" = \"$parentBaseClass\".\"ID\"");
    			$this->query->leftJoin($componentBaseClass, "\"$relationTable\".\"$componentField\" = \"$componentBaseClass\".\"ID\"");
    			if(ClassInfo::hasTable($componentClass)) {
    				$this->query->leftJoin($componentClass, "\"$relationTable\".\"$componentField\" = \"$componentClass\".\"ID\"");
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
		$subSelect= $subtractQuery->getFinalisedQuery();
		$subSelect->select($this->expressionForField($field, $subSelect));
		$this->where($this->expressionForField($field, $this).' NOT IN ('.$subSelect->sql().')');
	}

	/**
	 * Select the given fields from the given table
	 */
	public function selectFromTable($table, $fields) {
		$fieldExpressions = array_map(create_function('$item', 
			"return '\"$table\".\"' . \$item . '\"';"), $fields);
		
		$this->select($fieldExpressions);
	}

	/**
	 * Query the given field column from the database and return as an array.
	 */
	public function column($field = 'ID') {
		$query = $this->getFinalisedQuery();
		$query->select($this->expressionForField($field, $query));
		$this->ensureSelectContainsOrderbyColumns($query);

		return $query->execute()->column($field);
	}
	
	protected function expressionForField($field, $query) {
		// Special case for ID
		if($field == 'ID') {
			$baseClass = ClassInfo::baseDataClass($this->dataClass);
			return "\"$baseClass\".\"ID\"";

		} else {
		    return $query->expressionForField($field);
	    }
	}
	
	/**
	 * Clear the selected fields to start over
	 */
	public function clearSelect() {
		$this->query->select = array();

		return $this;
	}

	/**
	 * Select the given field expressions.  You must do your own escaping
	 */
	protected function select($fieldExpressions) {
		$this->query->select = array_merge($this->query->select, $fieldExpressions);
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
	function setQueryParam($key, $value) {
		$this->queryParams[$key] = $value;
	}
	
	/**
	 * Set an arbitrary query parameter, that can be used by decorators to add additional meta-data to the query.
	 */
	function getQueryParam($key) {
		if(isset($this->queryParams[$key])) return $this->queryParams[$key];
		else return null;
	}
	
}
