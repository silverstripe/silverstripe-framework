<?php

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
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
	 * @param DataQuery $query
	 * @return DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		return $this->oneFilter($query, true);
	}

	/**
	 * Excludes an exact match (equals) on a field value.
	 *
	 * @param DataQuery $query
	 * @return DataQuery
	 */
	protected function excludeOne(DataQuery $query) {
		return $this->oneFilter($query, false);
	}

	/**
	 * Applies a single match, either as inclusive or exclusive
	 *
	 * @param DataQuery $query
	 * @param bool $inclusive True if this is inclusive, or false if exclusive
	 * @return DataQuery
	 */
	protected function oneFilter(DataQuery $query, $inclusive) {
		$this->model = $query->applyRelation($this->relation);
		$field = $this->getDbName();
		$value = $this->getValue();

		// Null comparison check
		if($value === null) {
			$where = DB::get_conn()->nullCheckClause($field, $inclusive);
			return $query->where($where);
		}

		// Value comparison check
		$where = DB::get_conn()->comparisonClause(
			$field,
			null,
			true, // exact?
			!$inclusive, // negate?
			$this->getCaseSensitive(),
			true
		);
		// for != clauses include IS NULL values, since they would otherwise be excluded
		if(!$inclusive) {
			$nullClause = DB::get_conn()->nullCheckClause($field, true);
			$where .= " OR {$nullClause}";
		}
		return $query->where(array($where => $value));
	}

	/**
	 * Applies an exact match (equals) on a field value against multiple
	 * possible values.
	 *
	 * @param DataQuery $query
	 * @return DataQuery
	 */
	protected function applyMany(DataQuery $query) {
		return $this->manyFilter($query, true);
	}

	/**
	 * Excludes an exact match (equals) on a field value against multiple
	 * possible values.
	 *
	 * @param DataQuery $query
	 * @return DataQuery
	 */
	protected function excludeMany(DataQuery $query) {
		return $this->manyFilter($query, false);
	}

	/**
	 * Applies matches for several values, either as inclusive or exclusive
	 *
	 * @param DataQuery $query
	 * @param bool $inclusive True if this is inclusive, or false if exclusive
	 * @return DataQuery
	 */
	protected function manyFilter(DataQuery $query, $inclusive) {
		$this->model = $query->applyRelation($this->relation);
		$caseSensitive = $this->getCaseSensitive();

		// Check values for null
		$field = $this->getDbName();
		$values = $this->getValue();
		if(empty($values)) {
			throw new \InvalidArgumentException("Cannot filter {$field} against an empty set");
		}
		$hasNull = in_array(null, $values, true);
		if($hasNull) {
			$values = array_filter($values, function($value) {
				return $value !== null;
			});
		}

		$connective = '';
		if(empty($values)) {
			$predicate = '';
		} elseif($caseSensitive === null) {
			// For queries using the default collation (no explicit case) we can use the WHERE .. NOT IN .. syntax,
			// providing simpler SQL than many WHERE .. AND .. fragments.
			$column = $this->getDbName();
			$placeholders = DB::placeholders($values);
			if($inclusive) {
				$predicate = "$column IN ($placeholders)";
			} else {
				$predicate = "$column NOT IN ($placeholders)";
			}
		} else {
			// Generate reusable comparison clause
			$comparisonClause = DB::get_conn()->comparisonClause(
				$this->getDbName(),
				null,
				true, // exact?
				!$inclusive, // negate?
				$this->getCaseSensitive(),
				true
			);
			$count = count($values);
			if($count > 1) {
				$connective = $inclusive ? ' OR ' : ' AND ';
				$conditions = array_fill(0, $count, $comparisonClause);
				$predicate = implode($connective, $conditions);
			} else {
				$predicate = $comparisonClause;
			}
		}

		// Always check for null when doing exclusive checks (either AND IS NOT NULL / OR IS NULL)
		// or when including the null value explicitly (OR IS NULL)
		if($hasNull || !$inclusive) {
			// If excluding values which don't include null, or including
			// values which include null, we should do an `OR IS NULL`.
			// Otherwise we are excluding values that do include null, so `AND IS NOT NULL`.
			// Simplified from (!$inclusive && !$hasNull) || ($inclusive && $hasNull);
			$isNull = !$hasNull || $inclusive;
			$nullCondition = DB::get_conn()->nullCheckClause($field, $isNull);

			// Determine merge strategy
			if(empty($predicate)) {
				$predicate = $nullCondition;
			} else {
				// Merge null condition with predicate
				if($isNull) {
					$nullCondition = " OR {$nullCondition}";
				} else {
					$nullCondition = " AND {$nullCondition}";
				}
				// If current predicate connective doesn't match the same as the null connective
				// make sure to group the prior condition
				if($connective && (($connective === ' OR ') !== $isNull)) {
					$predicate = "({$predicate})";
				}
				$predicate .= $nullCondition;
			}
		}

		return $query->where(array($predicate => $values));
	}

	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
