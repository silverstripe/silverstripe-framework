<?php

namespace SilverStripe\ORM\Filters;

/**
 * Matches textual content with a substring match on a text fragment leading
 * to the end of the string.
 *
 * <code>
 *  "abcdefg" => "defg" # true
 *  "abcdefg" => "abcd" # false
 * </code>
 */
class EndsWithFilter extends PartialMatchFilter
{
    protected static $matchesEndsWith = true;

    protected function getMatchPattern($value)
    {
        return "%$value";
    }
}
