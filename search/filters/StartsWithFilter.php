<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Matches textual content with a substring match from the beginning
 * of the string.
 *
 * <code>
 *  "abcdefg" => "defg" # false
 *  "abcdefg" => "abcd" # true
 * </code>
 *
 * @package framework
 * @subpackage search
 */
class StartsWithFilter extends PartialMatchFilter {

	protected function getMatchPattern($value) {
		return "$value%";
	}
}
