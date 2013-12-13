<?php
/**
 * Base class for creating comparison filters, eg; greater than, less than, greater than or equal, etc
 *
 * If you extend this abstract class, you must implement getOperator() and and getInverseOperator
 *
 * getOperator() should return a string operator that will be applied to the filter,
 * eg; if getOperator() returns "<" then this will be a LessThan filter
 *
 * getInverseOperator() should return a string operator that evaluates the inverse of getOperator(),
 * eg; if getOperator() returns "<", then the inverse should be ">=
 *
 * @package framework
 * @subpackage search
 */
abstract class ComparisonFilter extends SearchFilter {

	/**
	 * Should return an operator to be used for comparisons
	 *
	 * @return string Operator
	 */
	abstract protected function getOperator();

	/**
	 * Should return an inverse operator to be used for comparisons
	 *
	 * @return string Inverse operator
	 */
	abstract protected function getInverseOperator();

	/**
	 * Applies a comparison filter to the query
	 * Handles SQL escaping for both numeric and string values
	 *
	 * @param DataQuery $query
	 * @return $this|DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$value = $this->getDbFormattedValue();

		if(is_numeric($value)) {
			$filter = sprintf("%s %s %s",
				$this->getDbName(), $this->getOperator(), Convert::raw2sql($value));
		} else {
			$filter = sprintf("%s %s '%s'",
				$this->getDbName(), $this->getOperator(), Convert::raw2sql($value));
		}

		return $query->where($filter);
	}

	/**
	 * Applies a exclusion(inverse) filter to the query
	 * Handles SQL escaping for both numeric and string values
	 *
	 * @param DataQuery $query
	 * @return $this|DataQuery
	 */
	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$value = $this->getDbFormattedValue();

		if(is_numeric($value)) {
			$filter = sprintf("%s %s %s",
				$this->getDbName(), $this->getInverseOperator(), Convert::raw2sql($value));
		} else {
			$filter = sprintf("%s %s '%s'",
				$this->getDbName(), $this->getInverseOperator(), Convert::raw2sql($value));
		}

		return $query->where($filter);
	}

	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
