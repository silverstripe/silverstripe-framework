<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Incomplete.
 * 
 * @todo add to tests
 * 
 * @package framework
 * @subpackage search
 */
class WithinRangeFilter extends SearchFilter {
	
	private $min;
	private $max;
	
	public function setMin($min) {
		$this->min = $min;
	}
	
	public function setMax($max) {
		$this->max = $max;
	}
	
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s >= '%s' AND %s <= '%s'",
			$this->getDbName(),
			Convert::raw2sql($this->min),
			$this->getDbName(),
			Convert::raw2sql($this->max)
		));
	}
	
}

