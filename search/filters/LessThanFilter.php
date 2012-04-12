<?php
/**
 * Selects numerical/date content smaller than the input
 *
 * @todo documentation
 * 
 * @package framework
 * @subpackage search
 */
class LessThanFilter extends SearchFilter {
	
	/**
	 * @return $query
	 */
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$value = $this->getDbFormattedValue();

		if(is_numeric($value)) $filter = sprintf("%s < %s", $this->getDbName(), Convert::raw2sql($value));
		else $filter = sprintf("%s < '%s'", $this->getDbName(), Convert::raw2sql($value));
		
		return $query->where($filter);
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
