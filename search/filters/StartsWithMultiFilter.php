<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Checks if a value starts with one of the items of in a given set.
 * @deprecated 3.1 Use StartsWithFilter instead
 * 
 * @todo Add negation (NOT IN)6
 * @package framework
 * @subpackage search
 */
class StartsWithMultiFilter extends SearchFilter {
	function __construct($fullName, $value = false, array $modifiers = array()) {
		Deprecation::notice('3.1', 'Use StartsWithFilter instead.');
		parent::__construct($fullName, $value, $modifiers);
	}
	
	public function apply(DataQuery $query) {
		if (!is_array($this->getValue())) {
			$values = explode(',',$this->getValue());
		} else {
			$values = $this->getValue();
		}
		$filter = new StartsWithFilter($this->getFullName(), $values, $this->getModifiers());
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
		$filter = new StartsWithFilter($this->getFullName(), $values, $this->getModifiers());
		return $filter->exclude($query);
	}

	protected function excludeOne(DataQuery $query) {
		/* NO OP */
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}
