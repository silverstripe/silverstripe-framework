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

	/**
	 * Apply the match filter to the given variable value
	 *
	 * @param string $value The raw value
	 * @return string
	 */
	protected function getMatchPattern($value) {
		return "%$value%";
	}

	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparisonClause = DB::get_conn()->comparisonClause(
			$this->getDbName(),
			null,
			false, // exact?
			false, // negate?
			$this->getCaseSensitive(),
			true
		);
		return $query->where(array(
			$comparisonClause => $this->getMatchPattern($this->getValue())
		));
	}

	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$whereClause = array();
		$comparisonClause = DB::get_conn()->comparisonClause(
			$this->getDbName(),
			null,
			false, // exact?
			false, // negate?
			$this->getCaseSensitive(),
			true
		);
		foreach($this->getValue() as $value) {
			$whereClause[] = array($comparisonClause => $this->getMatchPattern($value));
		}
		return $query->whereAny($whereClause);
	}

	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$comparisonClause = DB::get_conn()->comparisonClause(
			$this->getDbName(),
			null,
			false, // exact?
			true, // negate?
			$this->getCaseSensitive(),
			true
		);
		return $query->where(array(
			$comparisonClause => $this->getMatchPattern($this->getValue())
		));
	}

	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$values = $this->getValue();
		$comparisonClause = DB::get_conn()->comparisonClause(
			$this->getDbName(),
			null,
			false, // exact?
			true, // negate?
			$this->getCaseSensitive(),
			true
		);
		$parameters = array();
		foreach($values as $value) {
			$parameters[] = $this->getMatchPattern($value);
		}
		// Since query connective is ambiguous, use AND explicitly here
		$count = count($values);
		$predicate = implode(' AND ', array_fill(0, $count, $comparisonClause));
		return $query->where(array($predicate => $parameters));
	}

	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
