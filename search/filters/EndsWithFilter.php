<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Matches textual content with a substring match on a text fragment leading
 * to the end of the string.
 * 
 * <code>
 *  "abcdefg" => "defg" # true
 *  "abcdefg" => "abcd" # false
 * </code>
 * 
 * @package framework
 * @subpackage search
 */
class EndsWithFilter extends SearchFilter {
	
	/**
	 * Applies a match on the trailing characters of a field value.
	 *
	 * @return unknown
	 */
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s %s '%%%s'",
			$this->getDbName(),
			(DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE',
			Convert::raw2sql($this->getValue())
		));
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
