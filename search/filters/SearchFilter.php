<?php
/**
 * Base class for filtering implementations,
 * which work together with {@link SearchContext}
 * to create or amend a query for {@link DataObject} instances.
 * See {@link SearchContext} for more information.
 *
 * @package framework
 * @subpackage search
 */
abstract class SearchFilter extends Object {
	
	/**
	 * @var string Classname of the inspected {@link DataObject}
	 */
	protected $model;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string 
	 */
	protected $fullName;
	
	/**
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * @var string Name of a has-one, has-many or many-many relation (not the classname).
	 * Set in the constructor as part of the name in dot-notation, and used in 
	 * {@link applyRelation()}.
	 */
	protected $relation;
	
	/**
	 * @param string $fullName Determines the name of the field, as well as the searched database 
	 *  column. Can contain a relation name in dot notation, which will automatically join
	 *  the necessary tables (e.g. "Comments.Name" to join the "Comments" has-many relationship and
	 *  search the "Name" column when applying this filter to a SiteTree class).
	 * @param mixed $value
	 */
	function __construct($fullName, $value = false) {
		$this->fullName = $fullName;
		// sets $this->name and $this->relation
		$this->addRelation($fullName);
		$this->value = $value;
	}
	
	/**
	 * Called by constructor to convert a string pathname into
	 * a well defined relationship sequence.
	 *
	 * @param string $name
	 */
	protected function addRelation($name) {
		if (strstr($name, '.')) {
			$parts = explode('.', $name);
			$this->name = array_pop($parts);
			$this->relation = $parts;
		} else {
			$this->name = $name;
		}
	}
	
	/**
	 * Set the root model class to be selected by this
	 * search query.
	 *
	 * @param string $className
	 */	
	public function setModel($className) {
		$this->model = $className;
	}
	
	/**
	 * Set the current value to be filtered on.
	 *
	 * @param string $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}
	
	/**
	 * Accessor for the current value to be filtered on.
	 * Caution: Data is not escaped.
	 *
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * The original name of the field.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param String
	 */
	public function setName($name) {
		$this->name = $name;
	}
	
	/**
	 * The full name passed to the constructor,
	 * including any (optional) relations in dot notation.
	 * 
	 * @return string
	 */
	public function getFullName() {
		return $this->fullName;
	}

	/**
	 * @param String
	 */
	public function setFullName($name) {
		$this->fullName = $name;
	}
	
	/**
	 * Normalizes the field name to table mapping.
	 * 
	 * @return string
	 */
	function getDbName() {
		// Special handler for "NULL" relations
		if($this->name == "NULL") return $this->name;
		
		// SRM: This code finds the table where the field named $this->name lives
		// Todo: move to somewhere more appropriate, such as DataMapper, the magical class-to-be?
		$candidateClass = $this->model;
		while($candidateClass != 'DataObject') {
			if(DataObject::has_own_table($candidateClass) && singleton($candidateClass)->hasOwnTableDatabaseField($this->name)) break;
			$candidateClass = get_parent_class($candidateClass);
		}
		if($candidateClass == 'DataObject') user_error("Couldn't find field $this->name in any of $this->model's tables.", E_USER_ERROR);
		
		return "\"$candidateClass\".\"$this->name\"";
	}
	
	/**
	 * Return the value of the field as processed by the DBField class
	 *
	 * @return string
	 */
	function getDbFormattedValue() {
		// SRM: This code finds the table where the field named $this->name lives
		// Todo: move to somewhere more appropriate, such as DataMapper, the magical class-to-be?
		$candidateClass = $this->model;
		$dbField = singleton($this->model)->dbObject($this->name);
		$dbField->setValue($this->value);
		return $dbField->RAW();
	}

	
	/**
	 * Apply filter criteria to a SQL query.
	 *
	 * @param SQLQuery $query
	 * @return SQLQuery
	 */
	abstract public function apply(DataQuery $query);
	
	/**
	 * Determines if a field has a value,
	 * and that the filter should be applied.
	 * Relies on the field being populated with
	 * {@link setValue()}
	 * 
	 * @return boolean
	 */
	public function isEmpty() {
		return false;
	}
	
}
