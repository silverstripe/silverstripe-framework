<?php
/**
 * Matches textual content with a columnname = 'keyword' construct
 *
 * @todo case sensitivity switch
 * @todo documentation
 * 
 * @package sapphire
 * @subpackage search
 */
class ExactMatchFilter extends SearchFilter {
	
	/**
	 * Applies an exact match (equals) on a field value.
	 *
	 * @return unknown
	 */
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		return $query->where("{$this->getName()} = '{$this->value}'");
	}
	
}
?>