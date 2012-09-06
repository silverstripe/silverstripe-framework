<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Matches textual content with a substring match from the beginning
 * of the string.
 * 
 * <code>
 *  "abcdefg" => "defg" # false
 *  "abcdefg" => "abcd" # true
 * </code>
 * 
 * @package framework
 * @subpackage search
 */
class StartsWithFilter extends SearchFilter {
	
	/**
	 * Applies a match on the starting characters of a field value.
	 *
	 * @return DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s %s '%s%%'",
			$this->getDbName(),
			(DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE',
			Convert::raw2sql($this->getValue())
		));
	}

	/**
	 * Applies a match on the starting characters of a field value.
	 * Matches against one of the many values.
	 *
	 * @return DataQuery
	 */
	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$connectives = array();
		foreach($this->getValue() as $value) {
			$connectives[] = sprintf(
				"%s %s '%s%%'",
				$this->getDbName(),
				(DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE',
				Convert::raw2sql($value)
			);
		}
		$whereClause = implode(' OR ', $connectives);
		return $query->where($whereClause);
	}

	/**
	 * Excludes a match on the starting characters of a field value.
	 *
	 * @return DataQuery
	 */
	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s NOT %s '%s%%'",
			$this->getDbName(),
			(DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE',
			Convert::raw2sql($this->getValue())
		));
	}

	/**
	 * Excludes a match on the starting characters of a field value.
	 * Excludes a field if it matches any of the values.
	 *
	 * @return DataQuery
	 */
	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$connectives = array();
		foreach($this->getValue() as $value) {
			$connectives[] = sprintf(
				"%s NOT %s '%s%%'",
				$this->getDbName(),
				(DB::getConn() instanceof PostgreSQLDatabase) ? 'ILIKE' : 'LIKE',
				Convert::raw2sql($value)
			);
		}
		$whereClause = implode(' AND ', $connectives);
		return $query->where($whereClause);
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
