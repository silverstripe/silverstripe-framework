<?php
/**
 * Matches on rows where the field is not equal to the given value.
 * 
 * @package framework
 * @subpackage search
 */
class NegationFilter extends SearchFilter {
	
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s != '%s'",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
	}
	
}

