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
		return $query->where(sprintf(
			"%s LIKE '%%%s%%'",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
?>