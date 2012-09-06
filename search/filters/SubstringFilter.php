<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Uses a substring match against content in column rows.
 * @deprecated 3.0 Use PartialMatchFilter instead
 * 
 * @package framework
 * @subpackage search
 */
class SubstringFilter extends PartialMatchFilter {
	public function __construct($fullName, $value = false) {
		Deprecation::notice('3.0', 'PartialMatchFilter instead.');
		parent::__construct($fullName, $value);
	}

	public function apply(DataQuery $query) {
		$values = $this->getValue();
		$filter = new PartialMatchFilter($this->getFullName(), $values);
		return $filter->apply($query);
	}

	protected function applyOne(DataQuery $query) {
		/* NO OP */
	}

	public function exclude(DataQuery $query) {
		$values = $this->getValue();
		$filter = new PartialMatchFilter($this->getFullName(), $values);
		return $filter->exclude($query);
	}

	protected function excludeOne(DataQuery $query) {
		/* NO OP */
	}
	
	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}
}

