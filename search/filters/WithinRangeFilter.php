<?php
/**
 * @package sapphire
 * @subpackage search
 */

/**
 * Incomplete.
 * 
 * @todo add to tests
 * 
 * @package sapphire
 * @subpackage search
 */
class WithinRangeFilter extends SearchFilter {
	
	private $min;
	private $max;
	
	function setMin($min) {
		$this->min = $min;
	}
	
	function setMax($max) {
		$this->max = $max;
	}
	
	function apply(SQLQuery $query) {
		$query->where(sprintf(
			"%s >= %s AND %s <= %s",
			$this->getDbName(),
			Convert::raw2sql($this->min),
			$this->getDbName(),
			Convert::raw2sql($this->max)
		));
	}
	
}

?>