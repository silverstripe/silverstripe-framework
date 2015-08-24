<?php
/**
 * Matches on rows where the field is not equal to the given value.
 * 
 * @deprecated 3.1 Use ExactMatchFilter:not instead
 * @package framework
 * @subpackage search
 */
class NegationFilter extends ExactMatchFilter {
	function __construct($fullName, $value = false, array $modifiers = array()) {
		Deprecation::notice('4.0', 'Use ExactMatchFilter:not instead.');
		$modifiers[] = 'not';
		parent::__construct($fullName, $value, $modifiers);
	}
}

