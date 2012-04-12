<?php
/**
 * Selects numerical/date content greater than the input
 *
 * @todo documentation
 * 
 * @package framework
 * @subpackage search
 */
class GreaterThanFilter extends SearchFilter {
	
	/**
	 * @return $query
	 */
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s > '%s'",
			$this->getDbName(),
			Convert::raw2sql($this->getDbFormattedValue())
		));
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
