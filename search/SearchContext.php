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
* Each DataObject subclass can have multiple search contexts for different cases,
* e.g. for a limited frontend search and a fully featured backend search.
* By default, you can use {@link DataObject->getDefaultSearchContext()} which is automatically
* scaffolded. It uses {@link DataObject::$searchable_fields} to determine which fields
* to include.
* 
* @see http://doc.silverstripe.com/doku.php?id=searchcontext
*
* @package sapphire
* @subpackage search
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
	 * The logical connective used to join WHERE clauses. Defaults to AND.
	 * @var string
	 */
	public $connective = 'AND';
	
	/**
	 * A key value pair of values that should be searched for.
	 * The keys should match the field names specified in {@link self::$fields}.
	 * Usually these values come from a submitted searchform
	 * in the form of a $_REQUEST object.
	 * CAUTION: All values should be treated as insecure client input.
	 * 
	 * @param string $modelClass The base {@link DataObject} class that search properties related to.
	 * 						Also used to generate a set of result objects based on this class.
	 * @param FieldSet $fields Optional. FormFields mapping to {@link DataObject::$db} properties
	 *	 					which are to be searched. Derived from modelclass using 
	 *						{@link DataObject::scaffoldSearchFields()} if left blank.
	 * @param array $filters Optional. Derived from modelclass if left blank
	 */	
	function __construct($modelClass, $fields = null, $filters = null) {
		$this->modelClass = $modelClass;
		$this->fields = ($fields) ? $fields : new FieldSet();
		$this->filters = ($filters) ? $filters : array();
		
		parent::__construct();
	}
		
	/**
	 * Returns scaffolded search fields for UI.
	 *
	 * @return FieldSet
	 */
	public function getSearchFields() {
		return ($this->fields) ? $this->fields : singleton($this->modelClass)->scaffoldSearchFields();
		// $this->fields is causing weirdness, so we ignore for now, using the default scaffolding
		//return singleton($this->modelClass)->scaffoldSearchFields();
	}
	
	/**
	 * @todo move to SQLQuery
	 * @todo fix hack
	 */
	protected function applyBaseTableFields() {
		$classes = ClassInfo::dataClassesFor($this->modelClass);
		$fields = array("\"".ClassInfo::baseDataClass($this->modelClass).'".*');
		if($this->modelClass != $classes[0]) $fields[] = '"'.$classes[0].'".*';
		//$fields = array_keys($model->db());
		$fields[] = '"'.$classes[0].'".\"ClassName\" AS "RecordClassName"';
		return $fields;
	}
	
	/**
	 * Returns a SQL object representing the search context for the given
	 * list of query parameters.
	 *
	 * @param array $searchParams Map of search criteria, mostly taked from $_REQUEST.
	 *  If a filter is applied to a relationship in dot notation,
	 *  the parameter name should have the dots replaced with double underscores,
	 *  for example "Comments__Name" instead of the filter name "Comments.Name".
	 * @param string|array $sort Database column to sort on. 
	 *  Falls back to {@link DataObject::$default_sort} if not provided.
	 * @param string|array $limit 
	 * @param SQLQuery $existingQuery
	 * @return SQLQuery
	 */
	public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null) {
		$model = singleton($this->modelClass);
		
		if($existingQuery) {
			$query = $existingQuery;
		} else {
			$query = $model->extendedSQL();
		}

		$SQL_limit = Convert::raw2sql($limit);
		$query->limit($SQL_limit);

		$SQL_sort = (!empty($sort)) ? Convert::raw2sql($sort) : singleton($this->modelClass)->stat('default_sort');		
		$query->orderby($SQL_sort);
		
		// hack to work with $searchParems when it's an Object 
		$searchParamArray = array();
		if (is_object($searchParams)) {
			$searchParamArray = $searchParams->getVars();
		} else {
			$searchParamArray = $searchParams;
		}

 		foreach($searchParamArray as $key => $value) {
			$key = str_replace('__', '.', $key);
			if($filter = $this->getFilter($key)) {
				$filter->setModel($this->modelClass);
				$filter->setValue($value);
				if(! $filter->isEmpty()) {
					$filter->apply($query);
				}
			}
		}
		
		$query->connective = $this->connective;
		$query->distinct = true;
		
		$model->extend('augmentSQL', $query);
		
		return $query;
	}

	/**
	 * Returns a result set from the given search parameters.
	 *
	 * @todo rearrange start and limit params to reflect DataObject
	 * 
	 * @param array $searchParams
	 * @param string|array $sort
	 * @param string|array $limit
	 * @return DataObjectSet
	 */
	public function getResults($searchParams, $sort = false, $limit = false) {
		$searchParams = array_filter($searchParams, array($this,'clearEmptySearchFields'));
		
		$query = $this->getQuery($searchParams, $sort, $limit);
		
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
	
	/**
	 * Overwrite the current search context filter map.
	 *
	 * @param array $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
	}	
	
	/**
	 * Adds a instance of {@link SearchFilter}.
	 *
	 * @param SearchFilter $filter
	 */
	public function addFilter($filter) {
		$this->filters[$filter->getFullName()] = $filter;
	}
	
	/**
	 * Removes a filter by name.
	 *
	 * @param string $name
	 */
	public function removeFilterByName($name) {
		unset($this->filters[$name]);
	}
	
	/**
	 * Get the list of searchable fields in the current search context.
	 *
	 * @return FieldSet
	 */
	public function getFields() {
		return $this->fields; 
	}
	
	/**
	 * Apply a list of searchable fields to the current search context.
	 *
	 * @param FieldSet $fields
	 */
	public function setFields($fields) {
		$this->fields = $fields;
	}
	
	/**
	 * Adds a new {@link FormField} instance.
	 *
	 * @param FormField $field
	 */
	public function addField($field) {
		$this->fields->push($field);
	}
	
	/**
	 * Removes an existing formfield instance by its name.
	 *
	 * @param string $fieldName
	 */
	public function removeFieldByName($fieldName) {
		$this->fields->removeByName($fieldName);
	}
	
}
?>
