<?php
/**
 * Matches on rows where the field is not equal to the given value.
 * 
 * @deprecated 3.1 Use ExactMatchFilter:not instead
 * @package framework
 * @subpackage search
 */
class NegationFilter extends SearchFilter {
	function __construct($fullName, $value = false, array $modifiers = array()) {
		Deprecation::notice('3.1', 'Use ExactMatchFilter:not instead.');
		$modifiers[] = 'not';
		parent::__construct($fullName, $value, $modifiers);
	}
	
	public function apply(DataQuery $query) {
		$filter = new ExactMatchFilter($this->getFullName(), $this->getValue(), $this->getModifiers());
		return $filter->apply($query);
	}
	
	protected function applyOne(DataQuery $query) {
		/* NO OP */
	}
	
	public function exclude(DataQuery $query) {
		$filter = new ExactMatchFilter($this->getFullName(), $this->getValue(), $this->getModifiers());
		return $filter->exclude($query);
	}

	protected function excludeOne(DataQuery $query) {
		/* NO OP */
	}
}

