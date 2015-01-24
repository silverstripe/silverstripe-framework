<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Matches textual content with a substring match on a text fragment leading
 * to the end of the string.
 *
 * <code>
 *  "abcdefg" => "defg" # true
 *  "abcdefg" => "abcd" # false
 * </code>
 *
 * @package framework
 * @subpackage search
 */
class EndsWithFilter extends PartialMatchFilter {

	protected function getMatchPattern($value) {
		return "%$value";
	}
}
