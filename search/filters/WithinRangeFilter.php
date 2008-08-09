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
		$query->where("{$this->getName()} >= {$this->min} AND {$this->getName()} <= {$this->max}");
	}
	
}

?>