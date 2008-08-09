<?php
/**
* Manages searching of properties on one or more {@link DataObject}
* types, based on a given set of input parameters.
* SearchContext is intentionally decoupled from any controller-logic,
* it just receives a set of search parameters and an object class it acts on.
* 
* The default output of a SearchContext is either a {@link SQLQuery} object
* for further refinement, or a {@link DataObjectSet} that can be used to display
* search results, e.g. in a {@link TableListField} instance.
* 
* In case you need multiple contexts, consider namespacing your request parameters
* by using {@link FieldSet->namespace()} on the $fields constructor parameter.
* 
* @usedby {@link ModelAdmin}
* 
* @param string $modelClass The base {@link DataObject} class that search properties related to.
* 						Also used to generate a set of result objects based on this class.
* @param FieldSet $fields Optional. FormFields mapping to {@link DataObject::$db} properties
*	 					which are to be searched. Derived from modelclass using 
*						{@link DataObject::scaffoldSearchFields()} if left blank.
* @param array $filters Optional. Derived from modelclass if left blank
*/
class SearchContext extends Object {
	
	/**
	 * DataObject subclass to which search parameters relate to.
	 * Also determines as which object each result is provided.
	 *
	 * @var string
	 */
	protected $modelClass;
	
	/**
	 * FormFields mapping to {@link DataObject::$db} properties
	 * which are supposed to be searchable.
	 *
	 * @var FieldSet
	 */
	protected $fields;
	
	/**
	 * Array of {@link SearchFilter} subclasses.
	 *
	 * @var array
	 */
	protected $filters;
	
	/**
	 * A key value pair of values that should be searched for.
	 * The keys should match the field names specified in {@link self::$fields}.
	 * Usually these values come from a submitted searchform
	 * in the form of a $_REQUEST object.
	 * CAUTION: All values should be treated as insecure client input.
	 *
	 * @var array
	protected $params;
	 */
	
	/**
	 * Require either all search filters to be evaluated to true,
	 * or just a single one.
	 *
	 * @todo Not Implemented
	 * @var string
	 */
	protected $booleanSearchType = 'AND';
	
	
	function __construct($modelClass, $fields = null, $filters = null) {
		$this->modelClass = $modelClass;
		$this->fields = $fields;
		$this->filters = $filters;
		
		parent::__construct();
	}

	public function getSearchFields() {
		return ($this->fields) ? $this->fields : singleton($this->modelClass)->scaffoldSearchFields();
	}
	
	/**
	 * Get the query object augumented with all clauses from
	 * the connected {@link SearchFilter}s
	 * 
	 * @todo query generation
	 *
	 * @param array $searchParams
	 * @return SQLQuery
	 */
	public function getQuery($searchParams) {
		$q = new SQLQuery("*", $this->modelClass);
		$this->processFilters($q);
		return $q;
	}

	/**
	 * Light wrapper around {@link getQuery()}.
	 *
	 * @param array $searchParams
	 * @param int $start
	 * @param int $limit
	 * @return DataObjectSet
	 */
	public function getResults($searchParams, $start = false, $limit = false) {
		$q = $this->getQuery($searchParams);
		//$q->limit = $start ? "$start, $limit" : $limit;
		$output = new DataObjectSet();
		foreach($q->execute() as $row) {
			$className = $row['RecordClassName'];
			$output->push(new $className($row));
		}
		
		// do the setting of start/limit on the dataobjectset
		return $output;
	}

	/**
	 * @todo documentation
	 * @todo implementation
	 *
	 * @param array $searchFilters
	 * @param SQLQuery $query
	 */
	protected function processFilters($searchFilters, SQLQuery &$query) {
		foreach($this->filters as $filter) {
			$filter->updateQuery($searchFilters, $tableName, $query);
		}
	}
	
	// ############ Getters/Setters ###########
	
	
	public function getFields() {
		return $this->fields; 
	}
	
	public function setFields($fields) {
		$this->fields = $fields;
	}

	public function getFilters() {
		return $this->fields; 
	}
	
	public function setFilters($filters) {
		$this->filters = $filters;
	}
	
	function clearEmptySearchFields($value) {
		return ($value != '');
	}
	
	/**
	 * Placeholder, until I figure out the rest of the SQLQuery stuff
	 * and link the $searchable_fields array to the SearchContext
	 */
	public function getResultSet($fields) {
		$filter = "";
		$current = 1;
		$fields = array_filter($fields, array($this,'clearEmptySearchFields'));
		$length = count($fields);
		foreach($fields as $key=>$val) {
			// Array values come from more complex fields - for now let's just disable searching on them
			if (!is_array($val) && $val != '') {
				$filter .= "`$key`='$val'";
			} else {
				$length--;
			}
			if ($current < $length) {
				$filter .= " AND ";
			}
			$current++;
		}
		return DataObject::get($this->modelClass, $filter);
	}
	
}
?>