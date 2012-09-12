<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Matches textual content with a substring match from the beginning
 * of the string.
 * 
 * <code>
 *  "abcdefg" => "defg" # false
 *  "abcdefg" => "abcd" # true
 * </code>
 * 
 * @package framework
 * @subpackage search
 */
class StartsWithFilter extends SearchFilter {
	
	/**
	 * Applies a substring match on a field value.
	 *
	 * @return unknown
	 */
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s %s '%s%%'",
			$this->getDbName(),
			(DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE',
			Convert::raw2sql($this->getValue())
		));
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
