<?php
/**
 * @package sapphire
 * @subpackage search
 */

/**
 * Uses a substring match against content in column rows.
 * 
 * @package sapphire
 * @subpackage search
 */
class SubstringFilter extends SearchFilter {

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

?>