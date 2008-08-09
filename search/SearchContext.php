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
		
	function __construct($modelClass, $fields = null, $filters = null) {
		$this->modelClass = $modelClass;
		$this->fields = $fields;
		$this->filters = $filters;
		
		parent::__construct();
	}
		
	/**
	 * Returns scaffolded search fields for UI.
	 *
	 * @todo is this necessary in the SearchContext? - ModelAdmin could unwrap this and just use DataObject::scaffoldSearchFields
	 * @return FieldSet
	 */
	public function getSearchFields() {
		// $this->fields is causing weirdness, so we ignore for now, using the default scaffolding
		//return ($this->fields) ? $this->fields : singleton($this->modelClass)->scaffoldSearchFields();
		return singleton($this->modelClass)->scaffoldSearchFields();
	}
	
	/**
	 * Returns a SQL object representing the search context for the given
	 * list of query parameters.
	 *
	 * @param array $searchParams
	 * @return SQLQuery
	 */
	public function getQuery($searchParams, $start = false, $limit = false) {
		$model = singleton($this->modelClass);
		$fields = array_keys($model->db());
		$query = new SQLQuery($fields, $this->modelClass);
		foreach($searchParams as $key => $value) {
			$filter = $this->getFilter($key);
			if ($filter) {
				$query->where[] = $filter->apply($value);
			}
		}
		return $query;
	}

	/**
	 * Returns a result set from the given search parameters.
	 *
	 * @todo rearrange start and limit params to reflect DataObject
	 * 
	 * @param array $searchParams
	 * @param int $start
	 * @param int $limit
	 * @return DataObjectSet
	 */
	public function getResults($searchParams, $start = false, $limit = false) {
		$query = $this->getQuery($searchParams, $start, $limit);
		//
		// use if a raw SQL query is needed
		//$results = new DataObjectSet();
		//foreach($query->execute() as $row) {
		//	$className = $row['ClassName'];
		//	$results->push(new $className($row));
		//}
		//return $results;
		//
		return DataObject::get($this->modelClass, $query->getFilter(), "", "", $limit);
	}

	/**
	 * @todo documentation
	 * @todo implementation
	 *
	 * @param array $searchFilters
	 * @param SQLQuery $query
	 */
	protected function processFilters(SQLQuery $query, $searchParams) {
		$conditions = array();
		foreach($this->filters as $field => $filter) {
			if (strstr($field, '.')) {
				$path = explode('.', $field);
			} else {
				$conditions[] = $filter->apply($searchParams[$field]);
			}
		}
		$query->where = $conditions;
	}
	
	public function getFilter($name) {
		if (isset($this->filters[$name])) {
			return $this->filters[$name];
		} else {
			return null;
		}
	}
	
	public function getFields() {
		return $this->fields; 
	}
	
	public function setFields($fields) {
		$this->fields = $fields;
	}

	public function getFilters() {
		return $this->filters;
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