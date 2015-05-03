<?php

/**
 * Selects textual content with an exact match between column name and keyword.
 *
 * @todo case sensitivity switch
 * @todo documentation
 *
 * @package framework
 * @subpackage search
 */
class ExactMatchFilter extends SearchFilter {
	/**
	 * @param array $modifiers
	 */
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
	 * @param DataQuery $query
	 *
	 * @return DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);

		$value = $this->getValue();

		if($value === null) {
			return $query->where(
				sprintf('%s IS NULL', $this->getDbName())
			);
		}

		$where = DB::getConn()->comparisonClause(
			$this->getDbName(),
			Convert::raw2sql($value),
			true, // exact?
			false, // negate?
			$this->getCaseSensitive()
		);

		return $query->where($where);
	}

	/**
	 * Applies an exact match (equals) on a field value against multiple possible values.
	 *
	 * @param DataQuery $query
	 *
	 * @return DataQuery
	 */
	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);

		$modifiers = $this->getModifiers();

		$values = array();

		foreach((array) $this->getValue() as $value) {
			$values[] = Convert::raw2sql($value);
		}

		if(!in_array('case', $modifiers) && !in_array('nocase', $modifiers)) {
			$valueStr = "'" . implode("', '", $values) . "'";

			return $query->where(sprintf(
				'%s IN (%s)',
				$this->getDbName(),
				$valueStr
			));
		} else {
			foreach($values as &$v) {
				$v = DB::getConn()->comparisonClause(
					$this->getDbName(),
					$v,
					true, // exact?
					false, // negate?
					$this->getCaseSensitive()
				);
			}

			$where = implode(' OR ', $values);

			return $query->where($where);
		}
	}

	/**
	 * Excludes an exact match (equals) on a field value.
	 *
	 * @param DataQuery $query
	 *
	 * @return DataQuery
	 */
	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);

		$value = $this->getValue();

		if($value === null) {
			return $query->where(
				sprintf('%s IS NOT NULL', $this->getDbName())
			);
		}

		$where = DB::getConn()->comparisonClause(
			$this->getDbName(),
			Convert::raw2sql($value),
			true, // exact?
			true, // negate?
			$this->getCaseSensitive()
		);

		return $query->where($where);
	}

	/**
	 * Excludes an exact match (equals) on a field value against multiple possible values.
	 *
	 * @param DataQuery $query
	 *
	 * @return DataQuery
	 */
	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);

		$modifiers = $this->getModifiers();

		$values = array();

		foreach((array) $this->getValue() as $value) {
			$values[] = Convert::raw2sql($value);
		}

		if(!in_array('case', $modifiers) && !in_array('nocase', $modifiers)) {
			$valueStr = "'" . implode("', '", $values) . "'";

			return $query->where(sprintf(
				'%s NOT IN (%s)',
				$this->getDbName(),
				$valueStr
			));
		} else {
			foreach($values as &$v) {
				$v = DB::getConn()->comparisonClause(
					$this->getDbName(),
					$v,
					true, // exact?
					true, // negate?
					$this->getCaseSensitive()
				);
			}

			$where = implode(' OR ', $values);

			return $query->where($where);
		}
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
