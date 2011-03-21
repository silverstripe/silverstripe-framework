<?php
/**
 * Matches on rows where the field is not equal to the given value.
 * 
 * @package sapphire
 * @subpackage search
 */
class NegationFilter extends SearchFilter {
	
	public function apply(DataQuery $query) {
		return $query->filter(sprintf(
			"%s != '%s'",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
	}
	
}

?>