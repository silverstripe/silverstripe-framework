<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Incomplete.
 *
 * @todo add to tests
 *
 * @package framework
 * @subpackage search
 */
class WithinRangeFilter extends SearchFilter {

	private $min;
	private $max;

	public function setMin($min) {
		$this->min = $min;
	}

	public function setMax($max) {
		$this->max = $max;
	}

	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$predicate = sprintf('%1$s >= ? AND %1$s <= ?', $this->getDbName());
		return $query->where(array(
			$predicate => array(
				$this->min,
				$this->max
			)
		));
	}

	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$predicate = sprintf('%1$s < ? OR %1$s > ?', $this->getDbName());
		return $query->where(array(
			$predicate => array(
				$this->min,
				$this->max
			)
		));
	}
}
