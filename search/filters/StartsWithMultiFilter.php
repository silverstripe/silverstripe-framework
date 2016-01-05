<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Checks if a value starts with one of the items of in a given set.
 * @deprecated 3.1 Use StartsWithFilter instead
 *
 * @todo Add negation (NOT IN)6
 * @package framework
 * @subpackage search
 */
class StartsWithMultiFilter extends StartsWithFilter {
	function __construct($fullName, $value = false, array $modifiers = array()) {
		Deprecation::notice('4.0', 'Use StartsWithFilter instead.');
		parent::__construct($fullName, $value, $modifiers);
	}
}
