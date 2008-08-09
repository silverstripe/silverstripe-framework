<?php
/**
 * Uses a substring match against content in column rows.
 * 
 * @package sapphire
 * @subpackage search
 */
class SubstringFilter extends SearchFilter {

	public function apply(SQLQuery $query) {
		return $query->where("LOCATE({$this->name}, $value)");
	}
	
}

?>