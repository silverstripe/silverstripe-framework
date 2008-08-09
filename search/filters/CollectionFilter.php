<?php
/**
 * Checks if a value is in a given set.
 * SQL syntax used: Column IN ('val1','val2')
 * 
 * @todo Add negation (NOT IN)6
 * 
 * @author Silverstripe Ltd., Ingo Schommer (<firstname>@silverstripe.com) 
 */
class CollectionFilter extends SearchFilter {
	
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		$values = explode(',',$this->value);
		if(!$values) return false;
		
		for($i=0; $i<count($values); $i++) {
			if(!is_numeric($values[$i])) {
				// @todo Fix string replacement to only replace leading and tailing quotes
				$values[$i] = str_replace("'", '', $values[$i]);
				$values[$i] = Convert::raw2sql($values[$i]);
			}
		}
		$SQL_valueStr = "'" . implode("','", $values) . "'";
		return $query->where("{$this->getName()} IN ({$SQL_valueStr})");
	}
	
}
?>