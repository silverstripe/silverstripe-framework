<?php
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
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	public function setModel($className) {
		$this->model = $className;
	}
	
	/**
	 * Normalizes the field name to table mapping.
	 *
	 */
	protected function getName() {
		// SRM: This code finds the table where the field named $this->name lives
		// Todo: move to somewhere more appropriate, such as DataMapper, the magical class-to-be?
		$candidateClass = $this->model;
		while($candidateClass != 'DataObject') {
			if(singleton($candidateClass)->fieldExists($this->name)) break;
			$candidateClass = get_parent_class($candidateClass);
		}
		if($candidateClass == 'DataObject') user_error("Couldn't find field $this->name in any of $this->model's tables.", E_USER_ERROR);
		
		return $candidateClass . "." . $this->name;
	}
	
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
	 * Applies multiple-table inheritance to straight joins on the data objects
	 *
	 * @todo Should this be applied in SQLQuery->from instead? !!! 
	 * 
	 * @return void
	 */
	protected function applyJoin($query, $model, $component) {
		$query->leftJoin($component, "{$this->model}.ID = $component.{$model->getReverseAssociation($this->model)}ID");
	}
	
	/**
	 * Traverse the relationship fields, and add the table
	 * mappings to the query object state.
	 *
	 * @todo move join specific crap into SQLQuery
	 * 
	 * @param unknown_type $query
	 * @return unknown
	 */
	protected function applyRelation($query) {
		if (is_array($this->relation)) {
			$model = singleton($this->model);
			foreach($this->relation as $rel) {
				if ($component = $model->has_one($rel)) {
					$model = singleton($component);
					$this->applyJoin($query, $model, $component);
					$this->model = $component;
				} elseif ($component = $model->has_many($rel)) {
					$model = singleton($component);
					$this->applyJoin($query, $model, $component);
					$this->model = $component;
				}
			}
		}
		return $query;
	}
	
	/**
	 * Apply filter criteria to a SQL query.
	 *
	 * @param SQLQuery $query
	 */
	abstract public function apply(SQLQuery $query);
	
}
?>