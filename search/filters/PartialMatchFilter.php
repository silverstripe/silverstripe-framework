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
	protected function comparison($exclude = false) {
		$modifiers = $this->getModifiers();
		if(($extras = array_diff($modifiers, array('not', 'nocase', 'case'))) != array()) {
			throw new InvalidArgumentException(
				get_class($this) . ' does not accept ' . implode(', ', $extras) . ' as modifiers');
		}

		if(DB::getConn() instanceof PostgreSQLDatabase) {
			$nocaseComp = 'ILIKE';
			$caseComp = 'LIKE';
		} elseif(DB::getConn() instanceof SQLite3Database) {
			$nocaseComp = 'LIKE';
			$caseComp = 'GLOB';
		} else {
			$nocaseComp = 'LIKE';
			$caseComp = 'LIKE BINARY';
		}

		if(in_array('case', $modifiers)) {
			$comparison = $caseComp;
		} else {
			$comparison = $nocaseComp;
		}

		if($exclude) {
			$comparison = 'NOT ' . $comparison;
		}
		return $comparison;
	}
	
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparison = $this->comparison(false);
		$wildcard = $this->getWildcard();
		$where = sprintf(
			"%s %s '%s'", 
			$this->getDbName(), 
			$comparison, 
			$wildcard . Convert::raw2sql($this->getValue()) . $wildcard
		);

		return $query->where($where);
	}

	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$comparison = $this->comparison(false);
		$wildcard = $this->getWildcard();
		foreach($this->getValue() as $value) {
			$where[]= sprintf(
				"%s %s '%s'", 
				$this->getDbName(), 
				$comparison, 
				$wildcard . Convert::raw2sql($value) . $wildcard
			);
		}

		return $query->where(implode(' OR ', $where));
	}

	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparison = $this->comparison(true);
		$wildcard = $this->getWildcard();
		$where = sprintf(
			"%s %s '%s'", 
			$this->getDbName(), 
			$comparison, 
			$wildcard . Convert::raw2sql($this->getValue()) . $wildcard
		);
		
		return $query->where($where);
	}

	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$comparison = $this->comparison(true);
		$wildcard = $this->getWildcard();
		foreach($this->getValue() as $value) {
			$where[]= sprintf(
				"%s %s '%s'", 
				$this->getDbName(), 
				$comparison, 
				$wildcard . Convert::raw2sql($this->getValue()) . $wildcard
			);
		}

		return $query->where(implode(' AND ', $where));
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
