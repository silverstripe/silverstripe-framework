<?php
/**
 * @package sapphire
 * @subpackage search
 */

/**
 * Matches textual content with a LIKE '%keyword%' construct.
 *
 * @package sapphire
 * @subpackage search
 */
class PartialMatchFilter extends SearchFilter {
	
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		return $query->where("{$this->getDbName()} LIKE '%{$this->getValue()}%'");
	}
	
}
?>