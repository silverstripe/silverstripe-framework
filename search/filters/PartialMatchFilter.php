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
			if(in_array('case', $modifiers)) {
				$comparison = 'LIKE';
			} else {
				$comparison = 'ILIKE';
			}
		} elseif(in_array('case', $modifiers)) {
			$comparison = 'LIKE BINARY';
		} else {
			$comparison = 'LIKE';
		}
		if($exclude) {
			$comparison = 'NOT ' . $comparison;
		}
		return $comparison;
	}
	
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparison = $this->comparison(false);
		$where = sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($this->getValue()));

		return $query->where($where);
	}

	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$comparison = $this->comparison(false);
		foreach($this->getValue() as $value) {
			$where[]= sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($value));
		}

		return $query->where(implode(' OR ', $where));
	}

	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparison = $this->comparison(true);
		$where = sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($this->getValue()));
		
		return $query->where($where);
	}

	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$comparison = $this->comparison(true);
		foreach($this->getValue() as $value) {
			$where[]= sprintf("%s %s '%%%s%%'", $this->getDbName(), $comparison, Convert::raw2sql($value));
		}

		return $query->where(implode(' AND ', $where));
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
