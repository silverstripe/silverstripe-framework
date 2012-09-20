<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Checks if a value is in a given set.
 * SQL syntax used: Column IN ('val1','val2')
 * @deprecated 3.1 Use ExactMatchFilter instead
 * 
 * @package framework
 * @subpackage search
 */
class ExactMatchMultiFilter extends SearchFilter {
	function __construct($fullName, $value = false, array $modifiers = array()) {
		Deprecation::notice('3.1', 'Use ExactMatchFilter instead.');
		parent::__construct($fullName, $value, $modifiers);
	}
	
	public function apply(DataQuery $query) {
		if (!is_array($this->getValue())) {
			$values = explode(',',$this->getValue());
		} else {
			$values = $this->getValue();
		}
		$filter = new ExactMatchFilter($this->getFullName(), $values, $this->getModifiers());
		return $filter->apply($query);
	}

	protected function applyOne(DataQuery $query) {
		/* NO OP */
	}

	public function exclude(DataQuery $query) {
		if (!is_array($this->getValue())) {
			$values = explode(',',$this->getValue());
		} else {
			$values = $this->getValue();
		}
		$filter = new ExactMatchFilter($this->getFullName(), $values, $this->getModifiers());
		return $filter->exclude($query);
	}

	protected function excludeOne(DataQuery $query) {
		/* NO OP */
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
