<?php
/**
 * Matches textual content with a LIKE '%keyword%' construct.
 *
 * @package sapphire
 * @subpackage search
 */
class PartialMatchFilter extends SearchFilter {
	
	public function apply($value) {
		return "{$this->name} LIKE '%$value%'";
	}
	
}
?>