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

	public function setModifiers(array $modifiers) {
		if(($extras = array_diff($modifiers, array('not', 'nocase', 'case'))) != array()) {
			throw new InvalidArgumentException(
				get_class($this) . ' does not accept ' . implode(', ', $extras) . ' as modifiers');
		}

		parent::setModifiers($modifiers);
	}
	
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$modifiers = $this->getModifiers();
		$where = DB::getConn()->comparisonClause(
			$this->getDbName(),
			'%' . Convert::raw2sql($this->getValue()) . '%',
			false, // exact?
			false, // negate?
			$this->getCaseSensitive()
		);

		return $query->where($where);
	}

	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$modifiers = $this->getModifiers();
		foreach($this->getValue() as $value) {
			$where[]= DB::getConn()->comparisonClause(
				$this->getDbName(),
				'%' . Convert::raw2sql($value) . '%',
				false, // exact?
				false, // negate?
				$this->getCaseSensitive()
			);
		}

		return $query->where(implode(' OR ', $where));
	}

	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$modifiers = $this->getModifiers();
		$where = DB::getConn()->comparisonClause(
			$this->getDbName(),
			'%' . Convert::raw2sql($this->getValue()) . '%',
			false, // exact?
			true, // negate?
			$this->getCaseSensitive()
		);
		
		return $query->where($where);
	}

	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = array();
		$modifiers = $this->getModifiers();
		foreach($this->getValue() as $value) {
			$where[]= DB::getConn()->comparisonClause(
				$this->getDbName(),
				'%' . Convert::raw2sql($value) . '%',
				false, // exact?
				true, // negate?
				$this->getCaseSensitive()
			);
		}

		return $query->where(implode(' AND ', $where));
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
