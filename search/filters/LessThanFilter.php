<?php
/**
 * Selects numerical/date content less than the input
 *
 * Can be used by SearchContext and DataList->filter, eg;
 * Model::get()->filter("Field1:LessThan", $value);
 *
 * @package framework
 * @subpackage search
 */
class LessThanFilter extends ComparisonFilter {

	protected function getOperator() {
		return "<";
	}

	protected function getInverseOperator() {
		return ">=";
	}

}
