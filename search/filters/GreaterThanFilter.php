<?php
/**
 * Selects numerical/date content greater than the input
 *
 * @todo documentation
 * 
 * @package sapphire
 * @subpackage search
 */
class GreaterThanFilter extends SearchFilter {
	
	/**
	 * @return $query
	 */
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		return $query->where("{$this->getDbName()} > '{$this->getValue()}'");
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
?>