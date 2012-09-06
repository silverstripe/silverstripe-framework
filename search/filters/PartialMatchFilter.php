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
	
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparison = (DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE';
		$where = sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($this->getValue()));

		return $query->where($where);
	}

	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$comparison = (DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE';
		foreach($this->getValue() as $value) {
			$where[]= sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($value));
		}

		return $query->where(implode(' OR ', $where));
	}

	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparison = (DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE';
		$where = sprintf("%s NOT %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($this->getValue()));
		
		return $query->where($where);
	}

	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$comparison = (DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE';
		foreach($this->getValue() as $value) {
			$where[]= sprintf("%s NOT %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($value));
		}

		return $query->where(implode(' AND ', $where));
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
