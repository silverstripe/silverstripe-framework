<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Checks if a value starts with one of the items of in a given set.
 * SQL syntax used: Column IN ('val1','val2')
 * 
 * @todo Add negation (NOT IN)6
 * @package framework
 * @subpackage search
 */
class StartsWithMultiFilter extends SearchFilter {
	
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$values = explode(',', $this->getValue());
		
		foreach($values as $value) {
			$matches[] = sprintf("%s LIKE '%s%%'",
				$this->getDbName(),
				Convert::raw2sql(str_replace("'", '', $value))
			);
		}
		
		return $query->where(implode(" OR ", $matches));
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
