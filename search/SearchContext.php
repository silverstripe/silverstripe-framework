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
	 * @return FieldSet
	 */
	public function getSearchFields() {
		// $this->fields is causing weirdness, so we ignore for now, using the default scaffolding
		//return ($this->fields) ? $this->fields : singleton($this->modelClass)->scaffoldSearchFields();
		return singleton($this->modelClass)->scaffoldSearchFields();
	}
	
	/**
	 * @todo fix hack
	 */
	protected function applyBaseTableFields() {
		$classes = ClassInfo::dataClassesFor($this->modelClass);
		//Debug::dump($classes);
		//die();
		$fields = array($classes[0].'.*', $this->modelClass.'.*');
		//$fields = array_keys($model->db());
		$fields[] = $classes[0].'.ClassName AS RecordClassName';
		return $fields;
	}

	/**
	 * @todo fix hack
	 */
	protected function applyBaseTable() {
		$classes = ClassInfo::dataClassesFor($this->modelClass);
		return $classes[0];
	}
	
	/**
	 * @todo only works for one level deep of inheritance
	 * @todo fix hack
	 */
	protected function applyBaseTableJoin($query) {
		$classes = ClassInfo::dataClassesFor($this->modelClass);
		if (count($classes) > 1) $query->leftJoin($classes[1], "{$classes[1]}.ID = {$classes[0]}.ID");
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
		
		$fields = $this->applyBaseTableFields($model);
	
		$query = new SQLQuery($fields);
		
		$baseTable = $this->applyBaseTable();
		$query->from($baseTable);
		
		// SRM: This stuff is copied from DataObject, 
		if($this->modelClass != $baseTable) {
			$classNames = ClassInfo::subclassesFor($this->modelClass);
			$query->where[] = "`$baseTable`.ClassName IN ('" . implode("','", $classNames) . "')";
		}
		

		$this->applyBaseTableJoin($query);
		
		foreach($searchParams as $key => $value) {
			if ($value != '0') {
				$key = str_replace('__', '.', $key);
				$filter = $this->getFilter($key);
				if ($filter) {
					$filter->setModel($this->modelClass);
					$filter->setValue($value);
					$filter->apply($query);
				}
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
		$searchParams = array_filter($searchParams, array($this,'clearEmptySearchFields'));
		$query = $this->getQuery($searchParams, $start, $limit);

		// use if a raw SQL query is needed
		$results = new DataObjectSet();
		foreach($query->execute() as $row) {
			$className = $row['RecordClassName'];
			$results->push(new $className($row));
		}
		return $results;
		//
		//return DataObject::get($this->modelClass, $query->getFilter(), "", "", $limit);
	}

	/**
	 * Callback map function to filter fields with empty values from
	 * being included in the search expression.
	 *
	 * @param unknown_type $value
	 * @return boolean
	 */
	function clearEmptySearchFields($value) {
		return ($value != '');
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
		return $query;
	}
	
	/**
	 * Accessor for the filter attached to a named field.
	 *
	 * @param string $name
	 * @return SearchFilter
	 */
	public function getFilter($name) {
		if (isset($this->filters[$name])) {
			return $this->filters[$name];
		} else {
			return null;
		}
	}
	
	/**
	 * Get the map of filters in the current search context.
	 *
	 * @return array
	 */
	public function getFilters() {
		return $this->filters;
	}
	
	public function setFilters($filters) {
		$this->filters = $filters;
	}	
	
	/**
	 * Get the list of searchable fields in the current search context.
	 *
	 * @return array
	 */
	public function getFields() {
		return $this->fields; 
	}
	
	/**
	 * Apply a list of searchable fields to the current search context.
	 *
	 * @param array $fields
	 */
	public function setFields($fields) {
		$this->fields = $fields;
	}
	
	/**
	 * Placeholder, until I figure out the rest of the SQLQuery stuff
	 * and link the $searchable_fields array to the SearchContext
	 * 
	 * @deprecated in favor of getResults
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