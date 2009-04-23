<?php
/**
 * Matches on rows where the field is not equal to the given value.
 * 
 * @package sapphire
 * @subpackage search
 */
class NegationFilter extends SearchFilter {
	
	public function apply(SQLQuery $query) {
		return $query->where(sprintf(
			"%s != '%s'",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
	}
	
}

?>