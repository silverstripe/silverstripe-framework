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
	
	/**
	 * Applies an exact match (equals) on a field value.
	 *
	 * @return DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s = '%s'",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
	}

	/**
	 * Applies an exact match (equals) on a field value against multiple
	 * possible values.
	 *
	 * @return DataQuery
	 */
	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$values = array();
		foreach($this->getValue() as $value) {
			$values[] = Convert::raw2sql($value);
		}
		$valueStr = "'" . implode("', '", $values) . "'";
		return $query->where(sprintf(
			'%s IN (%s)',
			$this->getDbName(),
			$valueStr
		));
	}

	/**
	 * Excludes an exact match (equals) on a field value.
	 *
	 * @return DataQuery
	 */
	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s != '%s'",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
	}

	/**
	 * Excludes an exact match (equals) on a field value against multiple
	 * possible values.
	 *
	 * @return DataQuery
	 */
	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$values = array();
		foreach($this->getValue() as $value) {
			$values[] = Convert::raw2sql($value);
		}
		$valueStr = "'" . implode("', '", $values) . "'";
		return $query->where(sprintf(
			'%s NOT IN (%s)',
			$this->getDbName(),
			$valueStr
		));
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
