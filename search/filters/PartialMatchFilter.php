<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Matches textual content with a LIKE '%keyword%' construct.
 *
 * @package framework
 * @subpackage search
 */
class PartialMatchFilter extends SearchFilter {
	
	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$comparison = (DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE';
		if(is_array($this->getValue())) {
			foreach($this->getValue() as $value) {
				$where[]= sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($value));
			}

		} else {
			$where[] = sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($this->getValue()));
		}

		return $query->where(implode(' OR ', $where));
	}
	
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
