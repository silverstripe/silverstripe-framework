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
	function __construct($fullName, $value = false) {
		Deprecation::notice('3.0', 'PartialMatchFilter instead.');
		SearchFilter::__construct($fullName, $value);
	}

	public function apply(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"LOCATE('%s', %s) != 0",
			Convert::raw2sql($this->getValue()),
			$this->getDbName()
		));
	}

	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}

