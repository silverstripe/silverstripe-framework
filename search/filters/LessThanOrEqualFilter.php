<?php
/**
 * Selects numerical/date content less than or equal to the input
 *
 * Can be used by SearchContext and DataList->filter, eg;
 * Model::get()->filter("Field1:LessThanOrEqual", $value);
 *
 * @package framework
 * @subpackage search
 */
class LessThanOrEqualFilter extends ComparisonFilter {

	protected function getOperator() {
		return "<=";
	}

	protected function getInverseOperator() {
		return ">";
	}

}
