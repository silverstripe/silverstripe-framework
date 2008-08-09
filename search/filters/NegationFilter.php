<?php
/**
 * @package search
 * @subpackage filters
 */

/**
 * Matches on rows where the field is not equal to the given value.
 * 
 * @package search
 * @subpackage filters
 */
class NegationFilter extends SearchFilter {
	
	public function apply($value) {
		return "{$this->name} != '$value'";
	}
	
}

?>