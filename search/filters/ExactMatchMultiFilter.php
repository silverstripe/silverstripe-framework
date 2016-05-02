<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Checks if a value is in a given set.
 * SQL syntax used: Column IN ('val1','val2')
 * @deprecated 3.1 Use ExactMatchFilter instead
 *
 * @package framework
 * @subpackage search
 */
class ExactMatchMultiFilter extends ExactMatchFilter {
	function __construct($fullName, $value = false, array $modifiers = array()) {
		Deprecation::notice('4.0', 'Use ExactMatchFilter instead.');
		parent::__construct($fullName, $value, $modifiers);
	}
}
