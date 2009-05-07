<?php
/**
 * @package sapphire
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
 * @package sapphire
 * @subpackage search
 */
class EndsWithFilter extends SearchFilter {
	
	/**
	 * Applies a match on the trailing characters of a field value.
	 *
	 * @return unknown
	 */
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		$query->where($this->getDbName() . " LIKE '%" . Convert::raw2sql($this->getValue()) . "'");
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
?>