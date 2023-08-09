<?php

namespace SilverStripe\ORM\Filters;

/**
 * Matches textual content with a substring match from the beginning
 * of the string.
 *
 * <code>
 *  "abcdefg" => "defg" # false
 *  "abcdefg" => "abcd" # true
 * </code>
 */
class StartsWithFilter extends PartialMatchFilter
{
    protected static $matchesStartsWith = true;

    protected function getMatchPattern($value)
    {
        return "$value%";
    }
}
