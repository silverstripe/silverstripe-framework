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
	protected function comparison($exclude = false) {
		$modifiers = $this->getModifiers();
		if(($extras = array_diff($modifiers, array('not', 'nocase', 'case'))) != array()) {
			throw new InvalidArgumentException(
				get_class($this) . ' does not accept ' . implode(', ', $extras) . ' as modifiers');
		}
		if(!in_array('case', $modifiers) && !in_array('nocase', $modifiers)) {
			if($exclude) {
				return '!=';
			} else {
				return '=';
			}
		} elseif(DB::getConn() instanceof PostgreSQLDatabase) {
			if(in_array('case', $modifiers)) {
				$comparison = 'LIKE';
			} else {
				$comparison = 'ILIKE';
			}
		} elseif(in_array('case', $modifiers)) {
			$comparison = 'LIKE BINARY';
		} else {
			$comparison = 'LIKE';
		}
		if($exclude) {
			$comparison = 'NOT ' . $comparison;
		}
		return $comparison;
	}

	/**
	 * Applies an exact match (equals) on a field value.
	 *
	 * @return DataQuery
	 */
	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s %s '%s'",
			$this->getDbName(),
			$this->comparison(false),
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
		if($this->comparison(false) == '=') {
			// Neither :case nor :nocase
			$valueStr = "'" . implode("', '", $values) . "'";
			return $query->where(sprintf(
				'%s IN (%s)',
				$this->getDbName(),
				$valueStr
			));
		} else {
			foreach($values as &$v) {
				$v = sprintf(
					"%s %s '%s'",
					$this->getDbName(),
					$this->comparison(false),
					$v
				);
			}
			$where = implode(' OR ', $values);
			return $query->where($where);
		}
	}

	/**
	 * Excludes an exact match (equals) on a field value.
	 *
	 * @return DataQuery
	 */
	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s %s '%s'",
			$this->getDbName(),
			$this->comparison(true),
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
		if($this->comparison(false) == '=') {
			// Neither :case nor :nocase
			$valueStr = "'" . implode("', '", $values) . "'";
			return $query->where(sprintf(
				'%s NOT IN (%s)',
				$this->getDbName(),
				$valueStr
			));
		} else {
			foreach($values as &$v) {
				$v = sprintf(
					"%s %s '%s'",
					$this->getDbName(),
					$this->comparison(true),
					$v
				);
			}
			$where = implode(' OR ', $values);
			return $query->where($where);
		}
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
