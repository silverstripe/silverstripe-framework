<?php
/**
 * @package sapphire
 * @subpackage search
 */

/**
 * Checks if a value starts with one of the items of in a given set.
 * SQL syntax used: Column IN ('val1','val2')
 * 
 * @todo Add negation (NOT IN)6
 */
class StartsWithMultiFilter extends SearchFilter {
	
	public function apply(SQLQuery $query) {
		if($this->getValue()) {
			$query = $this->applyRelation($query);
			$values = explode(',',$this->getValue());
		
			foreach($values as $value) {
				$SQL_value = Convert::raw2sql(str_replace("'", '', $value));
				$matches[] = "{$this->getDbName()} LIKE '$SQL_value%'";
			}
			return $query->where(implode(" OR ", $matches));
		}
	}
	
}
?>