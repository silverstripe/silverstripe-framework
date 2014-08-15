<?php

/**
 * Represents a where condition that is dynamically generated. Maybe be stored
 * within a list of conditions, altered, and be allowed to affect the result
 * of the parent sql query during future execution.
 *
 * @package framework
 * @subpackage model
 */
interface SQLConditionGroup {

	/**
	 * Determines the resulting SQL along with parameters for the group
	 *
	 * @param array $parameters Out list of parameters
	 * @return string The complete SQL string for this item
	 */
	function conditionSQL(&$parameters);
}
