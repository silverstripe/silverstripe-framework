<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Selects textual content with an exact match between columnname and keyword.
 *
 * @todo case sensitivity switch
 * @todo documentation
 *
 * @package framework
 * @subpackage search
 */
class ExactMatchFilter extends SearchFilter {

	public function setModifiers(array $modifiers) {
		if(($extras = array_diff($modifiers, array('not', 'nocase', 'case'))) != array()) {
			throw new InvalidArgumentException(
				get_class($this) . ' does not accept ' . implode(', ', $extras) . ' as modifiers');
		}

		parent::setModifiers($modifiers);
	}

	/**
	 * Applies an exact match (equals) on a field value.
	 *
	 * @return DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = DB::get_conn()->comparisonClause(
			$this->getDbName(),
			null,
			true, // exact?
			false, // negate?
			$this->getCaseSensitive(),
			true
		);
		return $query->where(array($where => $this->getValue()));
	}

	/**
	 * Applies an exact match (equals) on a field value against multiple
	 * possible values.
	 *
	 * @return DataQuery
	 */
	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$caseSensitive = $this->getCaseSensitive();
		$values = $this->getValue();
		if($caseSensitive === null) {
			// For queries using the default collation (no explicit case) we can use the WHERE .. IN .. syntax,
			// providing simpler SQL than many WHERE .. OR .. fragments.
			$column = $this->getDbName();
			// If values is an empty array, fall back to 3.1 behaviour and use empty string comparison
			if(empty($values)) {
				$values = array('');
			}
			$placeholders = DB::placeholders($values);
			return $query->where(array(
				"$column IN ($placeholders)" => $values
			));
		} else {
			$whereClause = array();
			$comparisonClause = DB::get_conn()->comparisonClause(
				$this->getDbName(),
				null,
				true, // exact?
				false, // negate?
				$caseSensitive,
				true
			);
			foreach($values as $value) {
				$whereClause[] = array($comparisonClause => $value);
			}
			return $query->whereAny($whereClause);
		}
	}

	/**
	 * Excludes an exact match (equals) on a field value.
	 *
	 * @return DataQuery
	 */
	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$where = DB::get_conn()->comparisonClause(
			$this->getDbName(),
			null,
			true, // exact?
			true, // negate?
			$this->getCaseSensitive(),
			true
		);
		return $query->where(array($where => $this->getValue()));
	}

	/**
	 * Excludes an exact match (equals) on a field value against multiple
	 * possible values.
	 *
	 * @return DataQuery
	 */
	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$caseSensitive = $this->getCaseSensitive();
		$values = $this->getValue();
		if($caseSensitive === null) {
			// For queries using the default collation (no explicit case) we can use the WHERE .. NOT IN .. syntax,
			// providing simpler SQL than many WHERE .. AND .. fragments.
			$column = $this->getDbName();
			// If values is an empty array, fall back to 3.1 behaviour and use empty string comparison
			if(empty($values)) {
				$values = array('');
			}
			$placeholders = DB::placeholders($values);
			return $query->where(array(
				"$column NOT IN ($placeholders)" => $values
			));
		} else {
			// Generate reusable comparison clause
			$comparisonClause = DB::get_conn()->comparisonClause(
				$this->getDbName(),
				null,
				true, // exact?
				true, // negate?
				$this->getCaseSensitive(),
				true
			);
			// Since query connective is ambiguous, use AND explicitly here
			$count = count($values);
			$predicate = implode(' AND ', array_fill(0, $count, $comparisonClause));
			return $query->where(array($predicate => $values));
		}
	}

	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
