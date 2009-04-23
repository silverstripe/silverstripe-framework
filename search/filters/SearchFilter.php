<?php
/**
 * @package sapphire
 * @subpackage search
 */

/**
 * @todo documentation
 *
 * @package sapphire
 * @subpackage search
 */
abstract class SearchFilter extends Object {
	
	protected $model;
	protected $name;
	protected $value;
	protected $relation;
	
	function __construct($name, $value = false) {
		$this->addRelation($name);
		$this->value = $value;
	}
	
	/**
	 * Called by constructor to convert a string pathname into
	 * a well defined relationship sequence.
	 *
	 * @param unknown_type $name
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
			if(singleton($candidateClass)->fieldExists($this->name)) break;
			$candidateClass = get_parent_class($candidateClass);
		}
		if($candidateClass == 'DataObject') user_error("Couldn't find field $this->name in any of $this->model's tables.", E_USER_ERROR);
		
		return "`$candidateClass`.`$this->name`";
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
	 * Traverse the relationship fields, and add the table
	 * mappings to the query object state.
	 * 
	 * @todo try to make this implicitly triggered so it doesn't have to be manually called in child filters
	 * @param SQLQuery $query
	 * @return SQLQuery
	 */
	function applyRelation($query) {
		if (is_array($this->relation)) {
			foreach($this->relation as $rel) {
				$model = singleton($this->model);
				if ($component = $model->has_one($rel)) {	
					if(!$query->isJoinedTo($component)) {
						$foreignKey = $model->getReverseAssociation($component);
						$query->leftJoin($component, "`$component`.`ID` = `{$this->model}`.`{$foreignKey}ID`");
					}
					$this->model = $component;
				} elseif ($component = $model->has_many($rel)) {
					if(!$query->isJoinedTo($component)) {
					 	$ancestry = $model->getClassAncestry();
						$foreignKey = $model->getComponentJoinField($rel);
						$query->leftJoin($component, "`$component`.`{$foreignKey}` = `{$ancestry[0]}`.`ID`");
					}
					$this->model = $component;
				} elseif ($component = $model->many_many($rel)) {
					list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
					$parentBaseClass = ClassInfo::baseDataClass($parentClass);
					$componentBaseClass = ClassInfo::baseDataClass($componentClass);
					$query->innerJoin($relationTable, "`$relationTable`.`$parentField` = `$parentBaseClass`.`ID`");
					$query->leftJoin($componentClass, "`$relationTable`.`$componentField` = `$componentClass`.`ID`");
					$this->model = $componentClass;
				
				// Experimental support for user-defined relationships via a "(relName)Query" method
				// This will likely be dropped in 2.4 for a system that makes use of Lazy Data Lists.
				} elseif($model->hasMethod($rel.'Query')) {
					// Get the query representing the join - it should have "$ID" in the filter
					$newQuery = $model->{"{$rel}Query"}();
					if($newQuery) {
						// Get the table to join to
						$newModel = str_replace('`','',array_shift($newQuery->from));
						// Get the filter to use on the join
					 	$ancestry = $model->getClassAncestry();
						$newFilter = "(" . str_replace('$ID', "`{$ancestry[0]}`.`ID`" , implode(") AND (", $newQuery->where) ) . ")";
						$query->leftJoin($newModel, $newFilter);
						$this->model = $newModel;
					} else {
						$this->name = "NULL";
						return;
					}
				}
			}
		}
		return $query;
	}
	
	/**
	 * Apply filter criteria to a SQL query.
	 *
	 * @param SQLQuery $query
	 * @return SQLQuery
	 */
	abstract public function apply(SQLQuery $query);
	
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
?>