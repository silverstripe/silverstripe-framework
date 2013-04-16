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

	public function setModifiers(array $modifiers) {
		if(($extras = array_diff($modifiers, array('not', 'nocase', 'case'))) != array()) {
			throw new InvalidArgumentException(
				get_class($this) . ' does not accept ' . implode(', ', $extras) . ' as modifiers');
		}

		parent::setModifiers($modifiers);
	}
	
	/**
	 * Applies a match on the starting characters of a field value.
	 *
	 * @return DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$modifiers = $this->getModifiers();
		$where = DB::getConn()->comparisonClause(
			$this->getDbName(),
			Convert::raw2sql($this->getValue()) . '%',
			false, // exact?
			false, // negate?
			$this->getCaseSensitive()
		);
		return $query->where($where);
	}

	/**
	 * Applies a match on the starting characters of a field value.
	 * Matches against one of the many values.
	 *
	 * @return DataQuery
	 */
	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$modifiers = $this->getModifiers();
		$connectives = array();
		foreach($this->getValue() as $value) {
			$connectives[] = DB::getConn()->comparisonClause(
				$this->getDbName(),
				Convert::raw2sql($value) . '%',
				false, // exact?
				false, // negate?
				$this->getCaseSensitive()
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
		$modifiers = $this->getModifiers();
		$where = DB::getConn()->comparisonClause(
			$this->getDbName(),
			Convert::raw2sql($this->getValue()) . '%',
			false, // exact?
			true, // negate?
			$this->getCaseSensitive()
		);
		return $query->where($where);
	}

	/**
	 * Excludes a match on the starting characters of a field value.
	 * Excludes a field if it matches any of the values.
	 *
	 * @return DataQuery
	 */
	protected function excludeMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$modifiers = $this->getModifiers();
		$connectives = array();
		foreach($this->getValue() as $value) {
			$connectives[] = DB::getConn()->comparisonClause(
				$this->getDbName(),
				Convert::raw2sql($value) . '%',
				false, // exact?
				true, // negate?
				$this->getCaseSensitive()
			);
		}
		$whereClause = implode(' AND ', $connectives);
		return $query->where($whereClause);
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
